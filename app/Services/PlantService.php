<?php

namespace App\Services;

use App\Helpers\FileUploadHelper;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Plant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PlantService
{
    /**
     * Create a new plant with optional image upload and audit logging.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Plant
    {
        return DB::transaction(function () use ($data, $image) {
            $fileToUpload = $image ?? ($data['image'] ?? null) ?? ($data['image_url'] ?? null);

            if ($fileToUpload instanceof UploadedFile) {
                $data['image_url'] = FileUploadHelper::upload($fileToUpload, 'plants');
            }

            unset($data['image']);

            if (! array_key_exists('active', $data)) {
                $data['active'] = true;
            }

            $plant = Plant::create($data);

            $this->logAudit('insert', 'plants', $plant->id, null, $plant->toArray());

            return $plant;
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
