<?php

namespace App\Services;

use App\Helpers\FileUploadHelper;
use App\Models\AuditLog;
use App\Models\Inspection;
use App\Models\InspectionPhoto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InspectionService
{
    /**
     * Listar inspecciones con filtros y paginación.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Inspection::with(['customer', 'employee', 'services', 'photos']);

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('inspection_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('inspection_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('findings', 'like', "%{$search}%")
                    ->orWhere('terrain_notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('legal_name', 'like', "%{$search}%")
                            ->orWhere('commercial_name', 'like', "%{$search}%");
                    });
            });
        }

        $sortBy = $filters['sort_by'] ?? 'id';
        $sortDirection = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'inspection_date', 'created_at', 'status', 'total_area'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('id', 'desc');
        }

        $perPage = (int) ($filters['per_page'] ?? $filters['limit'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        return $query->paginate($perPage);
    }

    /**
     * Obtener una inspección por ID.
     */
    public function findById(int $id): Inspection
    {
        return Inspection::with(['customer', 'employee', 'services', 'photos'])->findOrFail($id);
    }

    /**
     * Crear una nueva inspección técnica de campo.
     */
    public function create(array $data, array $photos = []): Inspection
    {
        return DB::transaction(function () use ($data, $photos) {
            // Si no se asigna employee_id, usar el empleado del usuario autenticado si existe
            if (empty($data['employee_id'])) {
                $data['employee_id'] = Auth::user()?->employee_id;
            }

            // Calcular total_area automáticamente si no viene dado y se tienen dimensiones
            if (empty($data['total_area']) && ! empty($data['terrain_width']) && ! empty($data['terrain_length'])) {
                $data['total_area'] = (float) $data['terrain_width'] * (float) $data['terrain_length'];
            }

            $services = $data['services'] ?? [];
            unset($data['services']);

            $inspection = Inspection::create($data);

            // Asociar servicios sugeridos
            if (! empty($services)) {
                $syncData = [];
                foreach ($services as $service) {
                    $serviceId = is_array($service) ? $service['service_id'] : $service;
                    $quantity = is_array($service) ? ($service['suggested_quantity'] ?? 1) : 1;
                    $notes = is_array($service) ? ($service['notes'] ?? null) : null;

                    $syncData[$serviceId] = [
                        'suggested_quantity' => $quantity,
                        'notes' => $notes,
                    ];
                }
                $inspection->services()->sync($syncData);
            }

            // Guardar fotos de referencia visual
            foreach ($photos as $photoItem) {
                if ($photoItem instanceof UploadedFile) {
                    $path = FileUploadHelper::upload($photoItem, 'inspections');
                    $inspection->photos()->create([
                        'image_path' => $path,
                    ]);
                } elseif (is_array($photoItem) && isset($photoItem['file']) && $photoItem['file'] instanceof UploadedFile) {
                    $path = FileUploadHelper::upload($photoItem['file'], 'inspections');
                    $inspection->photos()->create([
                        'image_path' => $path,
                        'caption' => $photoItem['caption'] ?? null,
                    ]);
                }
            }

            $this->logAudit('insert', 'inspections', $inspection->id, null, $inspection->toArray());

            return $inspection->load(['customer', 'employee', 'services', 'photos']);
        });
    }

    /**
     * Actualizar una inspección.
     */
    public function update(int $id, array $data, array $newPhotos = []): Inspection
    {
        return DB::transaction(function () use ($id, $data, $newPhotos) {
            $inspection = Inspection::findOrFail($id);
            $oldData = $inspection->toArray();

            if (empty($data['total_area']) && ! empty($data['terrain_width']) && ! empty($data['terrain_length'])) {
                $data['total_area'] = (float) $data['terrain_width'] * (float) $data['terrain_length'];
            }

            $services = null;
            if (array_key_exists('services', $data)) {
                $services = $data['services'];
                unset($data['services']);
            }

            $inspection->update($data);

            if ($services !== null) {
                $syncData = [];
                foreach ($services as $service) {
                    $serviceId = is_array($service) ? $service['service_id'] : $service;
                    $quantity = is_array($service) ? ($service['suggested_quantity'] ?? 1) : 1;
                    $notes = is_array($service) ? ($service['notes'] ?? null) : null;

                    $syncData[$serviceId] = [
                        'suggested_quantity' => $quantity,
                        'notes' => $notes,
                    ];
                }
                $inspection->services()->sync($syncData);
            }

            foreach ($newPhotos as $photoItem) {
                if ($photoItem instanceof UploadedFile) {
                    $path = FileUploadHelper::upload($photoItem, 'inspections');
                    $inspection->photos()->create([
                        'image_path' => $path,
                    ]);
                } elseif (is_array($photoItem) && isset($photoItem['file']) && $photoItem['file'] instanceof UploadedFile) {
                    $path = FileUploadHelper::upload($photoItem['file'], 'inspections');
                    $inspection->photos()->create([
                        'image_path' => $path,
                        'caption' => $photoItem['caption'] ?? null,
                    ]);
                }
            }

            $this->logAudit('update', 'inspections', $inspection->id, $oldData, $inspection->fresh()->toArray());

            return $inspection->load(['customer', 'employee', 'services', 'photos']);
        });
    }

    /**
     * Subir fotos adicionales a una inspección.
     */
    public function addPhotos(int $inspectionId, array $photos): Inspection
    {
        return DB::transaction(function () use ($inspectionId, $photos) {
            $inspection = Inspection::findOrFail($inspectionId);

            foreach ($photos as $photoItem) {
                if ($photoItem instanceof UploadedFile) {
                    $path = FileUploadHelper::upload($photoItem, 'inspections');
                    $inspection->photos()->create([
                        'image_path' => $path,
                    ]);
                } elseif (is_array($photoItem) && isset($photoItem['file']) && $photoItem['file'] instanceof UploadedFile) {
                    $path = FileUploadHelper::upload($photoItem['file'], 'inspections');
                    $inspection->photos()->create([
                        'image_path' => $path,
                        'caption' => $photoItem['caption'] ?? null,
                    ]);
                }
            }

            $this->logAudit('update', 'inspections', $inspection->id, [], ['action' => 'photos_added']);

            return $inspection->load(['customer', 'employee', 'services', 'photos']);
        });
    }

    /**
     * Eliminar una foto de la inspección.
     */
    public function removePhoto(int $inspectionId, int $photoId): bool
    {
        return DB::transaction(function () use ($inspectionId, $photoId) {
            $photo = InspectionPhoto::where('inspection_id', $inspectionId)->findOrFail($photoId);
            FileUploadHelper::delete($photo->image_path);
            $photo->delete();

            $this->logAudit('update', 'inspections', $inspectionId, ['photo_deleted' => $photoId], null);

            return true;
        });
    }

    /**
     * Actualizar estado de la inspección (draft, submitted, reviewed).
     */
    public function updateStatus(int $id, string $status): Inspection
    {
        return DB::transaction(function () use ($id, $status) {
            $inspection = Inspection::findOrFail($id);
            $oldData = $inspection->toArray();

            $inspection->status = $status;
            $inspection->save();

            $this->logAudit('update', 'inspections', $inspection->id, $oldData, ['status' => $status]);

            return $inspection->load(['customer', 'employee', 'services', 'photos']);
        });
    }

    /**
     * Eliminar inspección (soft delete).
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $inspection = Inspection::findOrFail($id);
            $oldData = $inspection->toArray();

            $inspection->delete();

            $this->logAudit('delete', 'inspections', $inspection->id, $oldData, null);

            return true;
        });
    }

    /**
     * Registra auditoría interna.
     */
    private function logAudit(string $action, string $table, int $recordId, ?array $oldData, ?array $newData): void
    {
        $employeeId = Auth::user()?->employee_id;
        if (! $employeeId) {
            return;
        }

        AuditLog::create([
            'employee_id' => $employeeId,
            'action' => $action,
            'affected_table' => $table,
            'affected_record_id' => $recordId,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()?->ip(),
        ]);
    }
}
