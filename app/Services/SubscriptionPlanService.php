<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\SubscriptionPlan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionPlanService
{
    /**
     * HU-02: List plans with filters (active, search, frequency_unit).
     */
    public function search(array $filters = [], int $limit = 15): LengthAwarePaginator
    {
        $query = SubscriptionPlan::query()->withCount([
            'customers as active_subscribers_count' => function ($q) {
                $q->where('status', 'active');
            },
        ]);

        if (array_key_exists('active', $filters) && $filters['active'] !== null && $filters['active'] !== '') {
            $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
        } else {
            $query->where('active', true);
        }

        if (! empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(plan_code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(plan_name) LIKE ?', ["%{$search}%"]);
            });
        }

        if (! empty($filters['frequency_unit'])) {
            $query->where('frequency_unit', $filters['frequency_unit']);
        }

        return $query->orderBy('id', 'desc')->paginate($limit);
    }

    /**
     * HU-02: Get a single plan with its usage statistics.
     */
    public function getById(int $id): SubscriptionPlan
    {
        return SubscriptionPlan::withCount([
            'customers as active_subscribers_count' => function ($q) {
                $q->where('status', 'active');
            },
        ])->findOrFail($id);
    }

    /**
     * HU-01: Create a new subscription plan.
     */
    public function create(array $data): SubscriptionPlan
    {
        return DB::transaction(function () use ($data) {
            $data['active'] = $data['active'] ?? true;
            $plan = SubscriptionPlan::create($data);

            $this->logAudit('insert', 'subscription_plans', $plan->id, null, $plan->toArray());

            return $plan;
        });
    }

    /**
     * HU-03: Update an existing subscription plan.
     */
    public function update(int $id, array $data): SubscriptionPlan
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $oldData = $plan->toArray();

        return DB::transaction(function () use ($plan, $data, $oldData) {
            $plan->update($data);
            $this->logAudit('update', 'subscription_plans', $plan->id, $oldData, $plan->toArray());

            return $plan;
        });
    }

    /**
     * HU-04: Activate or deactivate a plan.
     */
    public function updateStatus(int $id, bool $active): SubscriptionPlan
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $oldData = $plan->toArray();

        return DB::transaction(function () use ($plan, $active, $oldData) {
            $plan->update(['active' => $active]);
            $this->logAudit('update', 'subscription_plans', $plan->id, $oldData, $plan->toArray());

            return $plan;
        });
    }

    /**
     * HU-04: Soft delete a plan, blocking it when there are active subscribers.
     */
    public function delete(int $id): void
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $activeSubscribers = $plan->customers()->where('status', 'active')->count();

        if ($activeSubscribers > 0) {
            throw ValidationException::withMessages([
                'plan' => ["No se puede eliminar el plan porque tiene {$activeSubscribers} cliente(s) activo(s) suscrito(s). Migre dichos clientes a otro plan primero."],
            ]);
        }

        $oldData = $plan->toArray();

        DB::transaction(function () use ($plan, $oldData) {
            $plan->delete();
            $this->logAudit('delete', 'subscription_plans', $plan->id, $oldData, null);
        });
    }

    /**
     * HU-05: Get the paginated list of customers subscribed to a plan.
     */
    public function subscribers(int $id, int $limit = 15): LengthAwarePaginator
    {
        $plan = SubscriptionPlan::findOrFail($id);

        return Customer::query()
            ->where('current_plan_id', $plan->id)
            ->with(['wallet', 'loyaltyPoint', 'currentPlan'])
            ->orderBy('id', 'desc')
            ->paginate($limit);
    }

    /**
     * Audit log helper.
     */
    private function logAudit(string $action, string $table, int $recordId, ?array $oldData, ?array $newData): void
    {
        $employee = Employee::first();
        if (! $employee) {
            return;
        }

        AuditLog::create([
            'employee_id' => auth()->id() ?? $employee->id,
            'affected_table' => $table,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
        ]);
    }
}
