<?php

namespace Tests\Feature\Transactions;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\TransactionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $auditorUser;

    private User $receptionistUser;

    private User $techUser;

    private Customer $customerA;

    private Customer $customerB;

    private SubscriptionPlan $plan;

    private Employee $adminEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Administrador')->firstOrFail();
        $auditorRole = Role::where('role_name', 'Auditor')->firstOrFail();
        $recepRole = Role::where('role_name', 'Recepcionista')->firstOrFail();
        $techRole = Role::where('role_name', 'Tecnico de campo')->firstOrFail();

        $this->adminEmployee = Employee::create([
            'full_name' => 'Carlos Administrador',
            'role_id' => $adminRole->id,
            'active' => true,
        ]);
        $this->adminUser = User::factory()->create(['employee_id' => $this->adminEmployee->id]);

        $auditorEmp = Employee::create([
            'full_name' => 'Ana Auditora',
            'role_id' => $auditorRole->id,
            'active' => true,
        ]);
        $this->auditorUser = User::factory()->create(['employee_id' => $auditorEmp->id]);

        $recepEmp = Employee::create([
            'full_name' => 'Laura Recepcionista',
            'role_id' => $recepRole->id,
            'active' => true,
        ]);
        $this->receptionistUser = User::factory()->create(['employee_id' => $recepEmp->id]);

        $techEmp = Employee::create([
            'full_name' => 'Roberto Tecnico',
            'role_id' => $techRole->id,
            'active' => true,
        ]);
        $this->techUser = User::factory()->create(['employee_id' => $techEmp->id]);

        $this->plan = SubscriptionPlan::create([
            'plan_code' => 'STD',
            'plan_name' => 'Plan Estándar',
            'frequency_value' => 15,
            'frequency_unit' => 'days',
            'visit_count' => 2,
            'maintenance_type' => 'Jardinería integral',
            'discount_percentage' => 10.00,
            'loyalty_points_multiplier' => 2.00,
            'active' => true,
        ]);

        $this->customerA = Customer::create([
            'legal_name' => 'Comercial Los Álamos S.A. de C.V.',
            'trade_name' => 'Los Álamos Café',
            'phone' => '7111-2222',
            'address' => 'Boulevard Los Próceres',
            'municipality' => 'San Salvador',
            'department' => 'San Salvador',
        ]);

        $this->customerB = Customer::create([
            'legal_name' => 'Residencial Montecristo',
            'trade_name' => null,
            'phone' => '7333-4444',
            'address' => 'Colonia Escalón',
            'municipality' => 'San Salvador',
            'department' => 'San Salvador',
        ]);
    }

    private function createQuote(array $attributes = []): Quote
    {
        static $counter = 100;
        $counter++;

        return Quote::create(array_merge([
            'quote_number' => "COT-TEST-{$counter}",
            'customer_id' => $this->customerA->id,
            'employee_id' => $this->adminEmployee->id,
            'applied_plan_id' => $this->plan->id,
            'base_price' => 100.00,
            'discounted_subtotal' => 90.00,
            'includes_vat' => false,
            'loyalty_points_used' => 0,
            'loyalty_points_earned' => 10,
            'total' => 90.00,
            'status' => 'paid',
        ], $attributes));
    }

    public function test_unauthenticated_user_cannot_access_transactions(): void
    {
        $response = $this->getJson('/api/v1/transactions');
        $response->assertStatus(401);
    }

    public function test_field_technician_is_forbidden_to_view_transactions(): void
    {
        Sanctum::actingAs($this->techUser);

        $response = $this->getJson('/api/v1/transactions');
        $response->assertStatus(403);
    }

    public function test_admin_auditor_and_receptionist_can_access_transactions(): void
    {
        foreach ([$this->adminUser, $this->auditorUser, $this->receptionistUser] as $user) {
            Sanctum::actingAs($user);
            $response = $this->getJson('/api/v1/transactions');
            $response->assertStatus(200);
        }
    }

    public function test_excludes_in_progress_pending_quotes(): void
    {
        Sanctum::actingAs($this->adminUser);

        // 1 Cotización pagada (debe aparecer)
        $paid = $this->createQuote([
            'quote_number' => 'COT-PAID-01',
            'status' => 'paid',
            'total' => 150.00,
        ]);

        // 1 Cotización en curso / pendiente (NO debe aparecer)
        $pending = $this->createQuote([
            'quote_number' => 'COT-PENDING-02',
            'status' => 'pending',
            'total' => 200.00,
        ]);

        // 1 Cotización declinada / cancelada (debe aparecer)
        $declined = $this->createQuote([
            'quote_number' => 'COT-DECLINED-03',
            'status' => 'pending',
            'total' => 300.00,
        ]);
        $declined->delete(); // Soft delete activa deleted_at

        $response = $this->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonFragment(['quoteNumber' => 'COT-PAID-01'])
            ->assertJsonFragment(['quoteNumber' => 'COT-DECLINED-03'])
            ->assertJsonMissing(['quoteNumber' => 'COT-PENDING-02']);
    }

    public function test_summary_calculates_accepted_and_declined_counts_and_amounts(): void
    {
        Sanctum::actingAs($this->adminUser);

        $this->createQuote(['status' => 'paid', 'total' => 100.00]);
        $this->createQuote(['status' => 'paid', 'total' => 150.00]);

        $declined = $this->createQuote(['status' => 'pending', 'total' => 80.00]);
        $declined->delete();

        $response = $this->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonPath('meta.summary.acceptedCount', 2)
            ->assertJsonPath('meta.summary.declinedCount', 1)
            ->assertJsonPath('meta.summary.totalCount', 3);

        $this->assertEquals(250.0, $response->json('meta.summary.totalAcceptedAmount'));
        $this->assertEquals(80.0, $response->json('meta.summary.totalDeclinedAmount'));
    }

    public function test_search_filters_by_customer_name_and_quote_number(): void
    {
        Sanctum::actingAs($this->adminUser);

        $quoteA = $this->createQuote([
            'quote_number' => 'COT-ALAMOS-77',
            'customer_id' => $this->customerA->id,
            'status' => 'paid',
        ]);

        $quoteB = $this->createQuote([
            'quote_number' => 'COT-MONTE-88',
            'customer_id' => $this->customerB->id,
            'status' => 'paid',
        ]);

        // Buscar por nombre del cliente A
        $response = $this->getJson('/api/v1/transactions?search=Álamos');
        $response->assertStatus(200)
            ->assertJsonFragment(['quoteNumber' => 'COT-ALAMOS-77'])
            ->assertJsonMissing(['quoteNumber' => 'COT-MONTE-88']);

        // Buscar por número de cotización de B
        $response2 = $this->getJson('/api/v1/transactions?search=MONTE-88');
        $response2->assertStatus(200)
            ->assertJsonFragment(['quoteNumber' => 'COT-MONTE-88'])
            ->assertJsonMissing(['quoteNumber' => 'COT-ALAMOS-77']);
    }

    public function test_filter_by_specific_customer(): void
    {
        Sanctum::actingAs($this->adminUser);

        $this->createQuote([
            'quote_number' => 'COT-CLI-A',
            'customer_id' => $this->customerA->id,
            'status' => 'paid',
        ]);

        $this->createQuote([
            'quote_number' => 'COT-CLI-B',
            'customer_id' => $this->customerB->id,
            'status' => 'paid',
        ]);

        $response = $this->getJson("/api/v1/transactions?customer_id={$this->customerA->id}");
        $response->assertStatus(200)
            ->assertJsonFragment(['quoteNumber' => 'COT-CLI-A'])
            ->assertJsonMissing(['quoteNumber' => 'COT-CLI-B']);
    }

    public function test_filter_by_status_type_performed_and_declined(): void
    {
        Sanctum::actingAs($this->adminUser);

        $this->createQuote([
            'quote_number' => 'COT-PERF-1',
            'status' => 'paid',
        ]);

        $declined = $this->createQuote([
            'quote_number' => 'COT-DECL-1',
            'status' => 'pending',
        ]);
        $declined->delete();

        // Filtrar solo efectuadas
        $respPerformed = $this->getJson('/api/v1/transactions?status_type=performed');
        $respPerformed->assertStatus(200)
            ->assertJsonFragment(['quoteNumber' => 'COT-PERF-1'])
            ->assertJsonMissing(['quoteNumber' => 'COT-DECL-1']);

        // Filtrar solo declinadas
        $respDeclined = $this->getJson('/api/v1/transactions?status_type=declined');
        $respDeclined->assertStatus(200)
            ->assertJsonFragment(['quoteNumber' => 'COT-DECL-1'])
            ->assertJsonMissing(['quoteNumber' => 'COT-PERF-1']);
    }

    public function test_returns_required_message_when_no_records_found(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/transactions?search=INEXISTENTE_XYZ');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'No se encontraron registros de trabajo en los filtros aplicados')
            ->assertJsonPath('data', []);
    }

    public function test_show_transaction_detail_successfully(): void
    {
        Sanctum::actingAs($this->adminUser);

        $quote = $this->createQuote([
            'quote_number' => 'COT-SHOW-01',
            'status' => 'paid',
            'total' => 250.00,
        ]);

        Payment::create([
            'quote_id' => $quote->id,
            'employee_id' => $this->adminEmployee->id,
            'payment_date' => now(),
            'amount_paid' => 250.00,
            'payment_method' => 'transfer',
        ]);

        $response = $this->getJson("/api/v1/transactions/{$quote->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.quoteNumber', 'COT-SHOW-01')
            ->assertJsonPath('data.status.state', 'performed')
            ->assertJsonPath('data.status.isPaid', true)
            ->assertJsonPath('data.status.isCancelled', false)
            ->assertJsonCount(1, 'data.payments');
    }

    public function test_show_returns_404_for_in_progress_pending_quote(): void
    {
        Sanctum::actingAs($this->adminUser);

        $pendingQuote = $this->createQuote([
            'quote_number' => 'COT-IN-PROGRESS',
            'status' => 'pending',
        ]);

        // No debe ser visible en el historial
        $response = $this->getJson("/api/v1/transactions/{$pendingQuote->id}");
        $response->assertStatus(404)
            ->assertJsonPath('message', 'Transacción no encontrada en el registro histórico.');
    }

    public function test_service_can_create_transaction_with_validation_and_audit(): void
    {
        Sanctum::actingAs($this->adminUser);

        // La cotización ya existe en el sistema (dominio de cotizaciones)
        $existingQuote = $this->createQuote([
            'quote_number' => 'COT-REG-TEST-1',
            'status' => 'pending',
            'total' => 90.00,
        ]);

        $transactionService = app(TransactionService::class);

        // TransactionService únicamente registra la transacción para la cotización existente
        $quote = $transactionService->createTransaction([
            'quote_id' => $existingQuote->id,
            'amount_paid' => 90.00,
            'payment_method' => 'cash',
        ]);

        $this->assertInstanceOf(Quote::class, $quote);
        $this->assertEquals('paid', $quote->status);
        $this->assertCount(1, $quote->payments);

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'quotes',
            'affected_record_id' => $quote->id,
            'action' => 'insert',
        ]);
    }

    public function test_service_create_transaction_throws_validation_exception_on_invalid_data(): void
    {
        $this->expectException(ValidationException::class);

        $transactionService = app(TransactionService::class);
        $transactionService->createTransaction([
            'quote_id' => 999999, // Inexistente
        ]);
    }
}
