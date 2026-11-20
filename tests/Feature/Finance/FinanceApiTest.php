<?php

namespace Tests\Feature\Finance;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Movement;
use App\Models\Payment;
use App\Models\Plant;
use App\Models\Purchase;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $consultaUser;

    private User $userWithoutRole;

    private User $inactiveAdminUser;

    private Employee $adminEmployee;

    private Customer $customer;

    private Plant $plant;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Administrador')->firstOrFail();
        $consultaRole = Role::firstOrCreate(
            ['role_name' => 'Consulta'],
            ['description' => 'Perfil de solo consulta.']
        );

        $this->adminEmployee = $this->createEmployee('Admin Finanzas', $adminRole);
        $this->adminUser = User::factory()->create(['employee_id' => $this->adminEmployee->id]);

        $consultaEmployee = $this->createEmployee('Consulta Finanzas', $consultaRole);
        $this->consultaUser = User::factory()->create(['employee_id' => $consultaEmployee->id]);

        $inactiveEmployee = $this->createEmployee('Admin Inactivo', $adminRole, false);
        $this->inactiveAdminUser = User::factory()->create(['employee_id' => $inactiveEmployee->id]);

        $this->userWithoutRole = User::factory()->create();

        $this->plan = SubscriptionPlan::create([
            'plan_code' => 'FIN-'.uniqid(),
            'plan_name' => 'Plan Finanzas',
            'frequency_value' => 30,
            'frequency_unit' => 'days',
            'visit_count' => 1,
            'active' => true,
        ]);

        $this->customer = Customer::create([
            'legal_name' => 'Cliente Finanzas',
            'address' => 'San Salvador',
            'municipality' => 'San Salvador Centro',
            'phone' => '2222-0000',
            'status' => 'active',
        ]);

        $this->plant = Plant::create([
            'name' => 'Planta Finanzas',
            'price' => 25.00,
            'category_type' => 'ornamental',
            'active' => true,
        ]);
    }

    public function test_guests_cannot_access_any_finance_endpoint(): void
    {
        foreach ($this->readEndpoints() as $endpoint) {
            $this->getJson($endpoint)->assertUnauthorized();
        }
    }

    public function test_only_admin_can_access_finance_endpoints(): void
    {
        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/finance/summary')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tiene permisos para realizar esta acción.');

        $this->actingAs($this->userWithoutRole, 'sanctum')
            ->getJson('/api/v1/finance/quotes')
            ->assertForbidden();

        $this->actingAs($this->inactiveAdminUser, 'sanctum')
            ->getJson('/api/v1/finance/purchases')
            ->assertForbidden();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/finance/movements')
            ->assertForbidden();

        $this->assertDatabaseHas('audit_log', [
            'employee_id' => $this->consultaUser->employee_id,
            'affected_table' => 'finance',
            'action' => 'denied',
        ]);

        $denied = AuditLog::where('action', 'denied')->latest('id')->firstOrFail();
        $this->assertSame('view', $denied->new_data['ability']);
        $this->assertSame('finance', $denied->new_data['resource']);

        $quote = $this->createQuote('COT-FIN-ACCESO', 100.00);
        $endpoints = array_map(
            fn (string $endpoint) => str_replace(
                '/finance/quotes/1/payments',
                "/finance/quotes/{$quote->id}/payments",
                $endpoint
            ),
            $this->readEndpoints()
        );

        foreach ($endpoints as $endpoint) {
            $this->actingAs($this->adminUser, 'sanctum')
                ->getJson($endpoint)
                ->assertOk();
        }
    }

    public function test_policy_grants_finance_access_only_to_active_admin_roles(): void
    {
        $this->assertTrue($this->adminUser->can('viewFinance'));
        $this->assertFalse($this->consultaUser->can('viewFinance'));
        $this->assertFalse($this->userWithoutRole->can('viewFinance'));
        $this->assertFalse($this->inactiveAdminUser->can('viewFinance'));
    }

    public function test_summary_calculates_indicators_and_filters_by_date_range(): void
    {
        $paidQuote = $this->createQuote('COT-FIN-001', 200.00, '2026-11-02 10:00:00');
        $partialQuote = $this->createQuote('COT-FIN-002', 100.00, '2026-10-02 10:00:00');

        $this->createPayment($paidQuote, 80.00, '2026-11-10 12:00:00');
        $this->createPayment($paidQuote, 120.00, '2026-11-12 12:00:00');
        $this->createPayment($partialQuote, 30.00, '2026-10-05 12:00:00');

        $this->createPurchase('CCF-001', 50.00, 6.50, '2026-11-05', 'Vivero Noviembre');
        $this->createPurchase('CCF-002', 100.00, 13.00, '2026-10-05', 'Vivero Octubre');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/summary')
            ->assertOk()
            ->assertJsonPath('data.totalProduction', 300)
            ->assertJsonPath('data.totalPaid', 230)
            ->assertJsonPath('data.totalPending', 70)
            ->assertJsonPath('data.partialPaymentsTotal', 30)
            ->assertJsonPath('data.totalPurchases', 169.5)
            ->assertJsonPath('data.profitability', 60.5);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/summary?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonPath('data.totalProduction', 200)
            ->assertJsonPath('data.totalPaid', 200)
            ->assertJsonPath('data.totalPending', 0)
            ->assertJsonPath('data.partialPaymentsTotal', 0)
            ->assertJsonPath('data.totalPurchases', 56.5)
            ->assertJsonPath('data.profitability', 143.5);
    }

    public function test_quotes_list_calculates_payment_status_and_applies_filters(): void
    {
        $paidQuote = $this->createQuote('COT-FIN-PAGADA', 100.00);
        $partialQuote = $this->createQuote('COT-FIN-PARCIAL', 200.00);
        $pendingQuote = $this->createQuote('COT-FIN-PENDIENTE', 300.00);

        $this->createPayment($paidQuote, 100.00, '2026-11-10 12:00:00');
        $this->createPayment($partialQuote, 50.00, '2026-11-11 12:00:00');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/quotes')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.paymentStatus.key', 'pending')
            ->assertJsonPath('data.0.paymentStatus.label', 'Pendiente')
            ->assertJsonPath('data.1.paymentStatus.key', 'partial')
            ->assertJsonPath('data.1.paymentStatus.label', 'Parcial')
            ->assertJsonPath('data.1.paidAmount', 50)
            ->assertJsonPath('data.1.pendingAmount', 150)
            ->assertJsonPath('data.2.paymentStatus.key', 'paid')
            ->assertJsonPath('data.2.paymentStatus.label', 'Pagado');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/quotes?payment_status=partial')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $partialQuote->id);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/quotes?payment_status=pagado')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paidQuote->id);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/quotes?quote_number=COT-FIN-PENDIENTE')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingQuote->id);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/quotes?customer_id='.$this->customer->id)
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/quotes?customer_id=999999')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_quote_payments_detail_returns_history_and_missing_quote_returns_404(): void
    {
        $quote = $this->createQuote('COT-FIN-ABONOS', 300.00);
        $this->createPayment($quote, 100.00, '2026-11-10 12:00:00');
        $this->createPayment($quote, 75.00, '2026-11-11 12:00:00');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/finance/quotes/{$quote->id}/payments")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.quote.quoteNumber', 'COT-FIN-ABONOS')
            ->assertJsonPath('meta.quote.paymentStatus.key', 'partial')
            ->assertJsonPath('meta.quote.paidAmount', 175)
            ->assertJsonPath('message', 'Abonos de la cotización obtenidos exitosamente.');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/quotes/999999/payments')
            ->assertNotFound();
    }

    public function test_finance_movements_lists_purchases_sales_and_summary(): void
    {
        Movement::create([
            'movement_type' => 'sale',
            'document_type' => 'Cotización Pagada',
            'document_number' => 'COT-FIN-MOV',
            'entity_name' => 'Cliente Finanzas',
            'movement_date' => '2026-11-05',
            'amount' => 200.00,
            'vat_amount' => 0.00,
            'total' => 200.00,
            'status' => 'completed',
        ]);

        Movement::create([
            'movement_type' => 'purchase',
            'document_type' => 'Crédito Fiscal',
            'document_number' => 'CCF-FIN-MOV',
            'entity_name' => 'Proveedor Finanzas',
            'movement_date' => '2026-11-02',
            'amount' => 50.00,
            'vat_amount' => 6.50,
            'total' => 56.50,
            'status' => 'completed',
        ]);

        Movement::create([
            'movement_type' => 'expense',
            'document_type' => 'Ticket Gasto',
            'document_number' => 'TCK-FIN-MOV',
            'entity_name' => 'Combustible',
            'movement_date' => '2026-10-02',
            'amount' => 20.00,
            'vat_amount' => 0.00,
            'total' => 20.00,
            'status' => 'completed',
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/movements')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'sale')
            ->assertJsonPath('summary.totalSales', 200)
            ->assertJsonPath('summary.totalPurchases', 56.5)
            ->assertJsonPath('summary.totalExpenses', 20)
            ->assertJsonPath('summary.utility', 123.5);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/movements?movement_type=compra')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documentNumber', 'CCF-FIN-MOV');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/movements?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/movements?type=gasto')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.entity.name', 'Combustible');
    }

    public function test_plant_sales_returns_plant_data_history_and_totals(): void
    {
        $novemberQuote = $this->createQuote('COT-PLANTA-NOV', 150.00, '2026-11-02 10:00:00');
        $octoberQuote = $this->createQuote('COT-PLANTA-OCT', 100.00, '2026-10-02 10:00:00');

        $this->createPlantItem($novemberQuote, 3, 25.00);
        $this->createPlantItem($novemberQuote, 2, 10.00);
        $this->createPlantItem($octoberQuote, 4, 25.00);

        $this->createPayment($novemberQuote, 80.00, '2026-11-10 12:00:00');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/finance/plants/{$this->plant->id}/sales")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.plant.id', $this->plant->id)
            ->assertJsonPath('meta.plant.name', 'Planta Finanzas')
            ->assertJsonPath('meta.plant.price', 25)
            ->assertJsonPath('meta.plant.categoryType', 'ornamental')
            ->assertJsonPath('meta.totals.unitsSold', 9)
            ->assertJsonPath('meta.totals.salesTotal', 195)
            ->assertJsonPath('meta.totals.quotesCount', 2)
            ->assertJsonPath('data.0.quoteNumber', 'COT-PLANTA-NOV')
            ->assertJsonPath('data.0.customerName', 'Cliente Finanzas')
            ->assertJsonPath('data.0.quantity', 2)
            ->assertJsonPath('data.0.unitPrice', 10)
            ->assertJsonPath('data.0.total', 20)
            ->assertJsonPath('data.0.paymentStatus.key', 'partial')
            ->assertJsonPath('data.0.paidAmount', 80)
            ->assertJsonPath('message', 'Historial de ventas de la planta obtenido exitosamente.');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/finance/plants/{$this->plant->id}/sales?from=2026-11-01&to=2026-11-30")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.totals.unitsSold', 5)
            ->assertJsonPath('meta.totals.salesTotal', 95)
            ->assertJsonPath('meta.totals.quotesCount', 1);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/plants/999999/sales')
            ->assertNotFound();
    }

    public function test_purchases_list_filters_by_date_and_supplier(): void
    {
        $this->createPurchase('CCF-100', 50.00, 6.50, '2026-11-05', 'Vivero Noviembre');
        $this->createPurchase('CCF-200', 100.00, 13.00, '2026-10-05', 'Vivero Octubre');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/purchases')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.documentNumber', 'CCF-100')
            ->assertJsonPath('data.0.amounts.total', 56.5);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/purchases?supplier=Octubre')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documentNumber', 'CCF-200');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/purchases?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documentNumber', 'CCF-100');
    }

    public function test_invalid_filters_return_validation_errors(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/summary?from=2026-11-30&to=2026-11-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/quotes?payment_status=cancelado')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_status']);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/finance/purchases?per_page=101')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * @return array<int, string>
     */
    private function readEndpoints(): array
    {
        return [
            '/api/v1/finance/movements',
            '/api/v1/finance/summary',
            '/api/v1/finance/quotes',
            '/api/v1/finance/quotes/1/payments',
            "/api/v1/finance/plants/{$this->plant->id}/sales",
            '/api/v1/finance/purchases',
        ];
    }

    private function createEmployee(string $fullName, Role $role, bool $active = true): Employee
    {
        return Employee::create([
            'full_name' => $fullName,
            'role_id' => $role->id,
            'active' => $active,
        ]);
    }

    private function createQuote(string $quoteNumber, float $total, ?string $createdAt = null): Quote
    {
        $quote = Quote::create([
            'quote_number' => $quoteNumber,
            'customer_id' => $this->customer->id,
            'employee_id' => $this->adminEmployee->id,
            'applied_plan_id' => $this->plan->id,
            'base_price' => $total,
            'discounted_subtotal' => $total,
            'total' => $total,
            'status' => 'pending',
            'includes_vat' => false,
        ]);

        if ($createdAt !== null) {
            Quote::whereKey($quote->id)->update(['created_at' => $createdAt]);
        }

        return $quote;
    }

    private function createPlantItem(Quote $quote, float $quantity, float $unitPrice): QuoteItem
    {
        return QuoteItem::create([
            'quote_id' => $quote->id,
            'item_type' => 'plant',
            'plant_id' => $this->plant->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => round($quantity * $unitPrice, 2),
        ]);
    }

    private function createPayment(Quote $quote, float $amount, string $date): Payment
    {
        return Payment::create([
            'quote_id' => $quote->id,
            'employee_id' => $this->adminEmployee->id,
            'payment_date' => $date,
            'amount_paid' => $amount,
            'payment_method' => 'transfer',
        ]);
    }

    private function createPurchase(
        string $documentNumber,
        float $taxedAmount,
        float $vatAmount,
        string $issueDate,
        string $supplierName
    ): Purchase {
        return Purchase::create([
            'issue_date' => $issueDate,
            'document_type' => 'Crédito Fiscal',
            'document_number' => $documentNumber,
            'supplier_tax_id' => '0614-150885-102-3',
            'supplier_name' => $supplierName,
            'employee_id' => $this->adminEmployee->id,
            'taxed_amount' => $taxedAmount,
            'exempt_amount' => 0.00,
            'non_taxable_amount' => 0.00,
            'vat_amount' => $vatAmount,
        ]);
    }
}
