<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CountryConfig;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CountryConfigService
{
    /**
     * List all country configurations.
     */
    public function all(): Collection
    {
        return CountryConfig::query()->orderBy('id')->get();
    }

    /**
     * Get a single country configuration by ID.
     */
    public function getById(int $id): CountryConfig
    {
        return CountryConfig::findOrFail($id);
    }

    /**
     * Create a new country configuration.
     */
    public function create(array $data): CountryConfig
    {
        return DB::transaction(function () use ($data) {
            $countryConfig = CountryConfig::create($data);

            $this->logAudit('insert', 'country_configs', $countryConfig->id, null, $countryConfig->toArray());

            return $countryConfig;
        });
    }

    /**
     * Update an existing country configuration.
     */
    public function update(int $id, array $data): CountryConfig
    {
        $countryConfig = CountryConfig::findOrFail($id);
        $oldData = $countryConfig->toArray();

        return DB::transaction(function () use ($countryConfig, $data, $oldData) {
            $countryConfig->update($data);
            $this->logAudit('update', 'country_configs', $countryConfig->id, $oldData, $countryConfig->toArray());

            return $countryConfig;
        });
    }

    /**
     * Delete a country configuration.
     */
    public function delete(int $id): void
    {
        $countryConfig = CountryConfig::findOrFail($id);
        $oldData = $countryConfig->toArray();

        DB::transaction(function () use ($countryConfig, $oldData) {
            $countryConfig->delete();
            $this->logAudit('delete', 'country_configs', $countryConfig->id, $oldData, null);
        });
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
