<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /**
     * Get paginated customers with filters (search, status, phone, date).
     */
    public function search(array $filters = [], int $limit = 15): LengthAwarePaginator
    {
        $query = Customer::query()
            ->with(['wallet', 'loyaltyPoint', 'currentPlan']);

        if (! empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(legal_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('tax_id', 'LIKE', "%{$search}%")
                    ->orWhere('nrc', 'LIKE', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['phone'])) {
            $query->where('phone', 'LIKE', '%'.trim($filters['phone']).'%');
        }

        if (! empty($filters['desde'])) {
            $query->whereDate('created_at', '>=', $filters['desde']);
        }

        if (! empty($filters['hasta'])) {
            $query->whereDate('created_at', '<=', $filters['hasta']);
        }

        return $query->orderBy('id', 'desc')->paginate($limit);
    }

    /**
     * Get single customer by ID
     */
    public function getById(int $id): Customer
    {
        return Customer::with(['wallet', 'loyaltyPoint', 'currentPlan'])->findOrFail($id);
    }

    /**
     * Create a new customer
     */
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = 'active';
            $customer = Customer::create($data);

            $customer->wallet()->create([
                'cash_balance' => 0.00,
                'reserved_subscription_balance' => 0.00,
            ]);

            $customer->loyaltyPoint()->create([
                'points_balance' => 0,
            ]);

            if (! empty($data['current_plan_id'])) {
                $customer->planHistories()->create([
                    'plan_id' => $data['current_plan_id'],
                    'start_date' => now()->toDateString(),
                ]);
            }

            $this->logAudit('insert', 'customers', $customer->id, null, $customer->toArray());

            return $customer->load(['wallet', 'loyaltyPoint', 'currentPlan']);
        });
    }

    /**
     * Update customer
     */
    public function update(int $id, array $data): Customer
    {
        $customer = Customer::findOrFail($id);
        $oldData = $customer->toArray();

        return DB::transaction(function () use ($customer, $data, $oldData) {
            $customer->update($data);
            $this->logAudit('update', 'customers', $customer->id, $oldData, $customer->toArray());

            return $customer->load(['wallet', 'loyaltyPoint', 'currentPlan']);
        });
    }

    /**
     * Update customer status
     */
    public function updateStatus(int $id, string $status): Customer
    {
        $customer = Customer::findOrFail($id);
        $oldData = $customer->toArray();

        return DB::transaction(function () use ($customer, $status, $oldData) {
            $customer->update(['status' => $status]);
            $this->logAudit('update', 'customers', $customer->id, $oldData, $customer->toArray());

            return $customer;
        });
    }

    /**
     * Soft delete customer
     */
    public function delete(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $oldData = $customer->toArray();

        DB::transaction(function () use ($customer, $oldData) {
            $customer->delete();
            $this->logAudit('delete', 'customers', $customer->id, $oldData, null);
        });
    }

    /**
     * Change customer plan
     */
    public function updatePlan(int $id, int $planId): Customer
    {
        $customer = Customer::findOrFail($id);

        if ($customer->current_plan_id == $planId) {
            throw ValidationException::withMessages([
                'plan_id' => ['El cliente ya tiene asignado este plan'],
            ]);
        }

        return DB::transaction(function () use ($customer, $planId) {
            if ($customer->current_plan_id) {
                $customer->planHistories()
                    ->where('plan_id', $customer->current_plan_id)
                    ->whereNull('end_date')
                    ->update(['end_date' => now()->toDateString()]);
            }

            $customer->planHistories()->create([
                'plan_id' => $planId,
                'start_date' => now()->toDateString(),
            ]);

            $oldData = $customer->toArray();
            $customer->update(['current_plan_id' => $planId]);

            $this->logAudit('update', 'customers', $customer->id, $oldData, $customer->toArray());

            return $customer->load(['currentPlan', 'wallet', 'loyaltyPoint']);
        });
    }

    /**
     * Audit log helper
     */
    private function logAudit(string $action, string $table, int $recordId, ?array $oldData, ?array $newData)
    {
        $employee = \App\Models\Employee::first();
        if (!$employee) {
            return;
        }
        $fallbackEmployeeId = $employee->id;

        AuditLog::create([
            'employee_id' => auth()->id() ?? $fallbackEmployeeId,
            'affected_table' => $table,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
        ]);
    }
}
