<?php

namespace Tests\Feature\Movements;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\ExpenseTicket;
use App\Models\Movement;
use App\Models\Purchase;
use App\Models\Quote;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\MovementService;
use App\Services\TransactionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MovementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $auditorUser;

    private User $receptionistUser;

    private User $techUser;

    private Employee $adminEmployee;

    private Customer $customer;

    private SubscriptionPlan $plan;

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

        $this->customer = Customer::create([
            'legal_name' => 'Empresa Los Laureles S.A.',
            'trade_name' => 'Los Laureles',
            'tax_id' => '0614-200190-102-1',
            'email' => 'contacto@laureles.com',
            'phone' => '2222-3333',
            'address' => 'Boulevard Los Próceres',
            'department' => 'San Salvador',
            'municipality' => 'San Salvador Centro',
            'status' => 'active',
            'current_plan_id' => $this->plan->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_movements(): void
    {
        $this->getJson('/api/v1/movements')->assertStatus(401);
    }

    public function test_field_technician_is_forbidden_from_movements(): void
    {
        Sanctum::actingAs($this->techUser);

        $this->getJson('/api/v1/movements')->assertStatus(403);
    }

    public function test_admin_auditor_and_receptionist_can_access_movements(): void
    {
        Sanctum::actingAs($this->adminUser);
        $this->getJson('/api/v1/movements')->assertStatus(200);

        Sanctum::actingAs($this->auditorUser);
        $this->getJson('/api/v1/movements')->assertStatus(200);

        Sanctum::actingAs($this->receptionistUser);
        $this->getJson('/api/v1/movements')->assertStatus(200);
    }

    public function test_service_can_record_purchase_helper_with_audit(): void
    {
        Sanctum::actingAs($this->adminUser);

        $purchase = Purchase::create([
            'supplier_name' => 'Vivero San Andrés S.A.',
            'supplier_tax_id' => '0614-150885-102-3',
            'supplier_nrc' => '12345-6',
            'document_type' => 'Crédito Fiscal',
            'document_number' => 'CCF-9021',
            'issue_date' => '2026-11-05',
            'employee_id' => $this->adminEmployee->id,
            'taxed_amount' => 100.00,
            'exempt_amount' => 10.00,
            'non_taxable_amount' => 0.00,
            'vat_amount' => 13.00,
            'notes' => 'Compra de abono orgánico y macetas',
        ]);

        $movementService = app(MovementService::class);
        $movement = $movementService->recordPurchase($purchase);

        $this->assertInstanceOf(Movement::class, $movement);
        $this->assertEquals('purchase', $movement->movement_type);
        $this->assertEquals(110.00, (float) $movement->amount);
        $this->assertEquals(13.00, (float) $movement->vat_amount);
        $this->assertEquals(123.00, (float) $movement->total);

        $this->assertDatabaseHas('movements', [
            'movement_type' => 'purchase',
            'movementable_type' => Purchase::class,
            'movementable_id' => $purchase->id,
            'document_number' => 'CCF-9021',
            'total' => 123.00,
        ]);

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'movements',
            'action' => 'insert',
        ]);
    }

    public function test_service_can_record_expense_helper_with_audit(): void
    {
        Sanctum::actingAs($this->adminUser);

        $ticket = ExpenseTicket::create([
            'category' => 'Pago de ayudantes',
            'description' => 'Jornal a ayudantes de poda en residencia Laureles',
            'amount' => 45.00,
            'expense_date' => '2026-11-06',
            'employee_id' => $this->adminEmployee->id,
        ]);

        $movementService = app(MovementService::class);
        $movement = $movementService->recordExpense($ticket);

        $this->assertInstanceOf(Movement::class, $movement);
        $this->assertEquals('expense', $movement->movement_type);
        $this->assertEquals(45.00, (float) $movement->amount);
        $this->assertEquals(45.00, (float) $movement->total);

        $this->assertDatabaseHas('movements', [
            'movement_type' => 'expense',
            'movementable_type' => ExpenseTicket::class,
            'movementable_id' => $ticket->id,
            'entity_name' => 'Pago de ayudantes',
            'total' => 45.00,
        ]);

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'movements',
            'action' => 'insert',
        ]);
    }

    public function test_automatic_sale_movement_is_created_when_quote_is_paid(): void
    {
        Sanctum::actingAs($this->adminUser);

        $quote = Quote::create([
            'quote_number' => 'COT-2026-9999',
            'customer_id' => $this->customer->id,
            'employee_id' => $this->adminEmployee->id,
            'applied_plan_id' => $this->plan->id,
            'base_price' => 200.00,
            'discounted_subtotal' => 180.00,
            'total' => 180.00,
            'status' => 'pending',
            'includes_vat' => false,
        ]);

        $transactionService = app(TransactionService::class);

        // Disparar formalización de pago
        $transactionService->createTransaction([
            'quote_id' => $quote->id,
            'amount_paid' => 180.00,
            'payment_method' => 'transfer',
            'payment_date' => '2026-11-07',
        ]);

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('movements', [
            'movement_type' => 'sale',
            'movementable_type' => Quote::class,
            'movementable_id' => $quote->id,
            'document_number' => 'COT-2026-9999',
            'entity_name' => 'Empresa Los Laureles S.A.',
            'total' => 180.00,
            'status' => 'completed',
        ]);
    }

    public function test_record_sale_from_quote_directly_supports_vat_and_is_idempotent(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Cotización con IVA incluido ($113 total -> $100 base + $13 IVA)
        $quoteWithVat = Quote::create([
            'quote_number' => 'COT-2026-IVA',
            'customer_id' => $this->customer->id,
            'employee_id' => $this->adminEmployee->id,
            'applied_plan_id' => $this->plan->id,
            'base_price' => 113.00,
            'discounted_subtotal' => 113.00,
            'total' => 113.00,
            'status' => 'pending',
            'includes_vat' => true,
        ]);

        $movementService = app(MovementService::class);

        // 1ra llamada: crea el movimiento con desglose de IVA
        $movement1 = $movementService->recordSaleFromQuote($quoteWithVat);
        $this->assertEquals(100.00, (float) $movement1->amount);
        $this->assertEquals(13.00, (float) $movement1->vat_amount);
        $this->assertEquals(113.00, (float) $movement1->total);

        // 2da llamada (Idempotencia): no duplica el movimiento
        $movement2 = $movementService->recordSaleFromQuote($quoteWithVat);
        $this->assertEquals($movement1->id, $movement2->id);

        $this->assertEquals(1, Movement::where('movementable_type', Quote::class)
            ->where('movementable_id', $quoteWithVat->id)
            ->count());
    }

    public function test_record_purchase_and_record_expense_are_idempotent(): void
    {
        Sanctum::actingAs($this->adminUser);

        $purchase = Purchase::create([
            'supplier_name' => 'Vivero Idempotente S.A.',
            'document_type' => 'Factura',
            'document_number' => 'FAC-777',
            'issue_date' => '2026-11-05',
            'taxed_amount' => 50.00,
            'vat_amount' => 6.50,
        ]);

        $ticket = ExpenseTicket::create([
            'category' => 'Herramientas',
            'description' => 'Manguera de riego',
            'amount' => 30.00,
            'expense_date' => '2026-11-06',
        ]);

        $movementService = app(MovementService::class);

        // Invocación doble de recordPurchase
        $p1 = $movementService->recordPurchase($purchase);
        $p2 = $movementService->recordPurchase($purchase);
        $this->assertEquals($p1->id, $p2->id);
        $this->assertEquals(1, Movement::where('movementable_type', Purchase::class)
            ->where('movementable_id', $purchase->id)
            ->count());

        // Invocación doble de recordExpense
        $e1 = $movementService->recordExpense($ticket);
        $e2 = $movementService->recordExpense($ticket);
        $this->assertEquals($e1->id, $e2->id);
        $this->assertEquals(1, Movement::where('movementable_type', ExpenseTicket::class)
            ->where('movementable_id', $ticket->id)
            ->count());
    }

    public function test_list_movements_calculates_summary_and_utility_in_real_time(): void
    {
        Sanctum::actingAs($this->adminUser);

        // 1. Venta ($300)
        Movement::create([
            'movement_type' => 'sale',
            'document_type' => 'Cotización Pagada',
            'document_number' => 'COT-001',
            'entity_name' => 'Cliente A',
            'movement_date' => '2026-11-01',
            'amount' => 300.00,
            'vat_amount' => 0.00,
            'total' => 300.00,
            'status' => 'completed',
        ]);

        // 2. Compra ($100 + $13 IVA = $113)
        Movement::create([
            'movement_type' => 'purchase',
            'document_type' => 'Crédito Fiscal',
            'document_number' => 'CCF-001',
            'entity_name' => 'Proveedor Vivero',
            'movement_date' => '2026-11-02',
            'amount' => 100.00,
            'vat_amount' => 13.00,
            'total' => 113.00,
            'status' => 'completed',
        ]);

        // 3. Gasto ($50)
        Movement::create([
            'movement_type' => 'expense',
            'document_type' => 'Ticket Gasto',
            'document_number' => 'TCK-001',
            'entity_name' => 'Combustible',
            'movement_date' => '2026-11-03',
            'amount' => 50.00,
            'vat_amount' => 0.00,
            'total' => 50.00,
            'status' => 'completed',
        ]);

        // Totales esperados:
        // totalSales = 300.00
        // totalPurchases = 113.00
        // totalExpenses = 50.00
        // totalIncome = 300.00
        // totalOutflow = 163.00
        // utility = 300 - 163 = 137.00
        // profitMargin = (137 / 300) * 100 = 45.67%

        $response = $this->getJson('/api/v1/movements');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('summary.totalSales', 300)
            ->assertJsonPath('summary.totalPurchases', 113)
            ->assertJsonPath('summary.totalExpenses', 50)
            ->assertJsonPath('summary.totalIncome', 300)
            ->assertJsonPath('summary.totalOutflow', 163)
            ->assertJsonPath('summary.utility', 137)
            ->assertJsonPath('summary.profitMarginPercentage', 45.67);
    }

    public function test_filter_movements_by_type_and_search(): void
    {
        Sanctum::actingAs($this->adminUser);

        Movement::create([
            'movement_type' => 'sale',
            'document_type' => 'Cotización Pagada',
            'document_number' => 'COT-101',
            'entity_name' => 'Juan Residencia',
            'movement_date' => '2026-11-01',
            'total' => 150.00,
            'status' => 'completed',
        ]);

        Movement::create([
            'movement_type' => 'purchase',
            'document_type' => 'Factura',
            'document_number' => 'FAC-500',
            'entity_name' => 'Ferretería El Tornillo',
            'movement_date' => '2026-11-02',
            'total' => 80.00,
            'status' => 'completed',
        ]);

        // Filtrar por tipo 'purchase'
        $responsePurchase = $this->getJson('/api/v1/movements?movement_type=purchase');
        $responsePurchase->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documentNumber', 'FAC-500');

        // Búsqueda por texto 'Ferretería'
        $responseSearch = $this->getJson('/api/v1/movements?search=Ferretería');
        $responseSearch->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.entity.name', 'Ferretería El Tornillo');
    }

    public function test_filter_movements_by_date_range(): void
    {
        Sanctum::actingAs($this->adminUser);

        Movement::create([
            'movement_type' => 'sale',
            'document_type' => 'Cotización Pagada',
            'document_number' => 'COT-OCT',
            'entity_name' => 'Cliente Octubre',
            'movement_date' => '2026-10-15',
            'total' => 100.00,
            'status' => 'completed',
        ]);

        Movement::create([
            'movement_type' => 'sale',
            'document_type' => 'Cotización Pagada',
            'document_number' => 'COT-NOV',
            'entity_name' => 'Cliente Noviembre',
            'movement_date' => '2026-11-05',
            'total' => 200.00,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/movements?start_date=2026-11-01&end_date=2026-11-30');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documentNumber', 'COT-NOV')
            ->assertJsonPath('summary.totalSales', 200);
    }
}
