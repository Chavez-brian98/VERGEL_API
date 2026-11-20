<?php

namespace Tests\Feature\Reports;

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
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $consultaUser;

    private User $userWithoutRole;

    private User $inactiveAdminUser;

    private Employee $adminEmployee;

    private Employee $secondEmployee;

    private Customer $customer;

    private Customer $secondCustomer;

    private Plant $plant;

    private SubscriptionPlan $plan;

    private SubscriptionPlan $secondPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Administrador')->firstOrFail();
        $consultaRole = Role::firstOrCreate(
            ['role_name' => 'Consulta'],
            ['description' => 'Perfil de solo consulta.']
        );

        $this->adminEmployee = $this->createEmployee('Admin Reportes', $adminRole);
        $this->adminUser = User::factory()->create(['employee_id' => $this->adminEmployee->id]);
        $this->secondEmployee = $this->createEmployee('Cobrador Reportes', $adminRole);

        $consultaEmployee = $this->createEmployee('Consulta Reportes', $consultaRole);
        $this->consultaUser = User::factory()->create(['employee_id' => $consultaEmployee->id]);

        $inactiveEmployee = $this->createEmployee('Admin Inactivo', $adminRole, false);
        $this->inactiveAdminUser = User::factory()->create(['employee_id' => $inactiveEmployee->id]);

        $this->userWithoutRole = User::factory()->create();

        $this->customer = $this->createCustomer('Cliente Reportes');
        $this->secondCustomer = $this->createCustomer('Cliente Secundario');

        $this->plant = Plant::create([
            'name' => 'Planta Reportes',
            'price' => 25.00,
            'category_type' => 'ornamental',
            'active' => true,
        ]);

        $this->plan = $this->createPlan('REP-MENSUAL', 'Plan Reportes');
        $this->secondPlan = $this->createPlan('REP-ANUAL', 'Plan Anual Reportes');
    }

    public function test_guests_cannot_access_any_report_endpoint(): void
    {
        foreach ($this->endpoints() as $endpoint) {
            $this->getJson($endpoint)->assertUnauthorized();
        }
    }

    public function test_only_admin_can_access_report_endpoints(): void
    {
        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/reports/sales')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tiene permisos para realizar esta acción.');

        $this->actingAs($this->userWithoutRole, 'sanctum')
            ->getJson('/api/v1/reports/customers')
            ->assertForbidden();

        $this->actingAs($this->inactiveAdminUser, 'sanctum')
            ->getJson('/api/v1/reports/profitability')
            ->assertForbidden();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/reports/quotes-status')
            ->assertForbidden();

        $this->assertDatabaseHas('audit_log', [
            'employee_id' => $this->consultaUser->employee_id,
            'affected_table' => 'reports',
            'action' => 'denied',
        ]);

        $denied = AuditLog::where('action', 'denied')->latest('id')->firstOrFail();
        $this->assertSame('view', $denied->new_data['ability']);
        $this->assertSame('reports', $denied->new_data['resource']);

        foreach ($this->endpoints() as $endpoint) {
            $this->actingAs($this->adminUser, 'sanctum')
                ->getJson($endpoint)
                ->assertOk();
        }
    }

    public function test_policy_grants_reports_access_only_to_active_admin_roles(): void
    {
        $this->assertTrue($this->adminUser->can('viewReports'));
        $this->assertFalse($this->consultaUser->can('viewReports'));
        $this->assertFalse($this->userWithoutRole->can('viewReports'));
        $this->assertFalse($this->inactiveAdminUser->can('viewReports'));
    }

    public function test_sales_report_groups_by_month_and_calculates_totals(): void
    {
        $november = $this->createQuote('COT-REP-001', 200.00, '2026-11-02 10:00:00');
        $october = $this->createQuote('COT-REP-002', 100.00, '2026-10-02 10:00:00');

        $this->createPayment($november, 80.00, '2026-11-10 12:00:00');
        $this->createPayment($november, 120.00, '2026-11-12 12:00:00');
        $this->createPayment($october, 30.00, '2026-10-05 12:00:00');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.period', '2026-10')
            ->assertJsonPath('data.0.totalSales', 100)
            ->assertJsonPath('data.0.totalPaid', 30)
            ->assertJsonPath('data.0.totalPending', 70)
            ->assertJsonPath('data.1.period', '2026-11')
            ->assertJsonPath('data.1.totalSales', 200)
            ->assertJsonPath('data.1.totalPaid', 200)
            ->assertJsonPath('data.1.totalPending', 0)
            ->assertJsonPath('meta.summary.totalQuotes', 2)
            ->assertJsonPath('meta.summary.totalSales', 300)
            ->assertJsonPath('meta.summary.totalPaid', 230)
            ->assertJsonPath('meta.summary.totalPending', 70)
            ->assertJsonPath('meta.summary.averageTicket', 150);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/sales?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.summary.totalSales', 200);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/sales?desde=2026-11-01&hasta=2026-11-30')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_customers_report_ranks_customers_by_billing(): void
    {
        $first = $this->createQuote('COT-REP-CLI-1', 200.00);
        $second = $this->createQuote('COT-REP-CLI-2', 100.00);
        $third = $this->createQuote('COT-REP-CLI-3', 50.00, null, $this->secondCustomer);

        $this->createPayment($first, 80.00, '2026-11-10 12:00:00');
        $this->createPayment($second, 50.00, '2026-11-11 12:00:00');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.customerId', $this->customer->id)
            ->assertJsonPath('data.0.quotesCount', 2)
            ->assertJsonPath('data.0.totalBilled', 300)
            ->assertJsonPath('data.0.totalPaid', 130)
            ->assertJsonPath('data.0.totalPending', 170)
            ->assertJsonPath('data.1.customerId', $this->secondCustomer->id)
            ->assertJsonPath('meta.summary.customersCount', 2)
            ->assertJsonPath('meta.summary.totalBilled', 350)
            ->assertJsonPath('meta.summary.totalPaid', 130)
            ->assertJsonPath('meta.summary.totalPending', 220);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/customers?customer_id='.$this->secondCustomer->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customerId', $this->secondCustomer->id);
    }

    public function test_plants_report_aggregates_units_and_revenue(): void
    {
        $quote = $this->createQuote('COT-REP-PLANTA', 150.00);
        $this->createPlantItem($quote, 3, 25.00);
        $this->createPlantItem($quote, 2, 10.00);
        $secondQuote = $this->createQuote('COT-REP-PLANTA-2', 100.00);
        $this->createPlantItem($secondQuote, 4, 25.00);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/plants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.plantId', $this->plant->id)
            ->assertJsonPath('data.0.unitsSold', 9)
            ->assertJsonPath('data.0.salesTotal', 195)
            ->assertJsonPath('data.0.quotesCount', 2)
            ->assertJsonPath('data.0.averageUnitPrice', 20)
            ->assertJsonPath('meta.summary.plantsCount', 1)
            ->assertJsonPath('meta.summary.unitsSold', 9)
            ->assertJsonPath('meta.summary.salesTotal', 195);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/plants?plant_id='.$this->plant->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_collections_report_groups_by_employee_and_payment_method(): void
    {
        $quote = $this->createQuote('COT-REP-COBROS', 300.00);
        $this->createPayment($quote, 80.00, '2026-11-10 12:00:00', $this->adminEmployee, 'transfer');
        $this->createPayment($quote, 120.00, '2026-11-12 12:00:00', $this->adminEmployee, 'cash');
        $this->createPayment($quote, 30.00, '2026-11-13 12:00:00', $this->secondEmployee, 'transfer');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/collections')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.employeeId', $this->adminEmployee->id)
            ->assertJsonPath('data.0.paymentsCount', 2)
            ->assertJsonPath('data.0.totalCollected', 200)
            ->assertJsonCount(2, 'data.0.methods')
            ->assertJsonFragment(['key' => 'transfer', 'totalCollected' => 30])
            ->assertJsonPath('meta.summary.paymentsCount', 3)
            ->assertJsonPath('meta.summary.totalCollected', 230);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/collections?employee_id='.$this->secondEmployee->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.totalCollected', 30);
    }

    public function test_quotes_status_report_returns_all_three_statuses(): void
    {
        $paid = $this->createQuote('COT-REP-PAGADA', 200.00);
        $partial = $this->createQuote('COT-REP-PARCIAL', 100.00);
        $this->createQuote('COT-REP-PENDIENTE', 50.00);

        $this->createPayment($paid, 200.00, '2026-11-10 12:00:00');
        $this->createPayment($partial, 30.00, '2026-11-11 12:00:00');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/quotes-status')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.key', 'paid')
            ->assertJsonPath('data.0.quotesCount', 1)
            ->assertJsonPath('data.0.totalPaid', 200)
            ->assertJsonPath('data.1.key', 'partial')
            ->assertJsonPath('data.1.quotesCount', 1)
            ->assertJsonPath('data.1.totalPaid', 30)
            ->assertJsonPath('data.2.key', 'pending')
            ->assertJsonPath('data.2.quotesCount', 1)
            ->assertJsonPath('meta.summary.totalQuotes', 3)
            ->assertJsonPath('meta.summary.totalBilled', 350)
            ->assertJsonPath('meta.summary.totalPaid', 230)
            ->assertJsonPath('meta.summary.totalPending', 120);
    }

    public function test_purchases_report_groups_by_supplier(): void
    {
        $this->createPurchase('CCF-REP-001', 50.00, 6.50, '2026-11-05', 'Vivero Noviembre');
        $this->createPurchase('CCF-REP-002', 100.00, 13.00, '2026-10-05', 'Vivero Octubre');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/purchases')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.supplier.name', 'Vivero Octubre')
            ->assertJsonPath('data.0.documentsCount', 1)
            ->assertJsonPath('data.0.amounts.total', 113)
            ->assertJsonPath('data.1.supplier.name', 'Vivero Noviembre')
            ->assertJsonPath('data.1.amounts.total', 56.5)
            ->assertJsonPath('meta.summary.documentsCount', 2)
            ->assertJsonPath('meta.summary.totalVat', 19.5)
            ->assertJsonPath('meta.summary.totalPurchases', 169.5);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/purchases?supplier=Octubre')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.supplier.name', 'Vivero Octubre');
    }

    public function test_profitability_report_groups_movements_by_month(): void
    {
        $this->createMovement('sale', 'COT-REP-MOV', 200.00, '2026-11-05');
        $this->createMovement('purchase', 'CCF-REP-MOV', 56.50, '2026-11-02');
        $this->createMovement('expense', 'TCK-REP-MOV', 20.00, '2026-10-02');
        $this->createMovement('sale', 'COT-REP-CANCEL', 999.00, '2026-11-06', 'cancelled');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/profitability')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.period', '2026-10')
            ->assertJsonPath('data.0.expenses', 20)
            ->assertJsonPath('data.0.utility', -20)
            ->assertJsonPath('data.1.period', '2026-11')
            ->assertJsonPath('data.1.sales', 200)
            ->assertJsonPath('data.1.purchases', 56.5)
            ->assertJsonPath('data.1.utility', 143.5)
            ->assertJsonPath('data.1.marginPercentage', 71.75)
            ->assertJsonPath('meta.summary.totalSales', 200)
            ->assertJsonPath('meta.summary.totalPurchases', 56.5)
            ->assertJsonPath('meta.summary.totalExpenses', 20)
            ->assertJsonPath('meta.summary.totalUtility', 123.5)
            ->assertJsonPath('meta.summary.profitMarginPercentage', 61.75);
    }

    public function test_subscriptions_report_groups_by_plan(): void
    {
        $this->createSubscription($this->plan, 100.00, 10.00, 'active', '2026-11-02');
        $this->createSubscription($this->plan, 50.00, 0.00, 'completed', '2026-10-02');
        $this->createSubscription($this->secondPlan, 25.00, 0.00, 'cancelled', '2026-11-03');

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/subscriptions')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.plan.id', $this->plan->id)
            ->assertJsonPath('data.0.subscriptionsCount', 2)
            ->assertJsonPath('data.0.activeCount', 1)
            ->assertJsonPath('data.0.completedCount', 1)
            ->assertJsonPath('data.0.totalPaid', 150)
            ->assertJsonPath('data.0.reservedBalance', 10)
            ->assertJsonPath('meta.summary.subscriptionsCount', 3)
            ->assertJsonPath('meta.summary.activeSubscriptions', 1)
            ->assertJsonPath('meta.summary.totalPaid', 175);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/subscriptions?plan_id='.$this->secondPlan->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.plan.id', $this->secondPlan->id)
            ->assertJsonPath('data.0.cancelledCount', 1);
    }

    public function test_invalid_filters_return_validation_errors(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/sales?from=2026-11-30&to=2026-11-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/customers?per_page=101')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/reports/quotes-status?customer_id=999999')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    /**
     * @return array<int, string>
     */
    private function endpoints(): array
    {
        return [
            '/api/v1/reports/sales',
            '/api/v1/reports/customers',
            '/api/v1/reports/plants',
            '/api/v1/reports/collections',
            '/api/v1/reports/quotes-status',
            '/api/v1/reports/purchases',
            '/api/v1/reports/profitability',
            '/api/v1/reports/subscriptions',
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

    private function createCustomer(string $legalName): Customer
    {
        return Customer::create([
            'legal_name' => $legalName,
            'address' => 'San Salvador',
            'municipality' => 'San Salvador Centro',
            'phone' => '2222-0000',
            'status' => 'active',
        ]);
    }

    private function createPlan(string $code, string $name): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'plan_code' => $code,
            'plan_name' => $name,
            'frequency_value' => 30,
            'frequency_unit' => 'days',
            'visit_count' => 1,
            'price' => 100.00,
            'active' => true,
        ]);
    }

    private function createQuote(
        string $quoteNumber,
        float $total,
        ?string $createdAt = null,
        ?Customer $customer = null
    ): Quote {
        $quote = Quote::create([
            'quote_number' => $quoteNumber,
            'customer_id' => ($customer ?? $this->customer)->id,
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

    private function createPayment(
        Quote $quote,
        float $amount,
        string $date,
        ?Employee $employee = null,
        string $method = 'transfer'
    ): Payment {
        return Payment::create([
            'quote_id' => $quote->id,
            'employee_id' => ($employee ?? $this->adminEmployee)->id,
            'payment_date' => $date,
            'amount_paid' => $amount,
            'payment_method' => $method,
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

    private function createMovement(
        string $type,
        string $documentNumber,
        float $total,
        string $date,
        string $status = 'completed'
    ): Movement {
        return Movement::create([
            'movement_type' => $type,
            'document_type' => 'Documento Reporte',
            'document_number' => $documentNumber,
            'entity_name' => 'Entidad Reporte',
            'movement_date' => $date,
            'amount' => $total,
            'vat_amount' => 0.00,
            'total' => $total,
            'status' => $status,
        ]);
    }

    private function createSubscription(
        SubscriptionPlan $plan,
        float $amountPaid,
        float $reservedBalance,
        string $status,
        string $paymentDate
    ): Subscription {
        return Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $plan->id,
            'payment_date' => $paymentDate,
            'amount_paid' => $amountPaid,
            'reserved_balance' => $reservedBalance,
            'status' => $status,
        ]);
    }
}
