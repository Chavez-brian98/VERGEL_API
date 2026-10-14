<?php

namespace App\Services;

use App\Helpers\FileUploadHelper;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Plant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlantService
{
    /**
     * Obtiene el listado paginado de plantas aplicando filtros dinámicos con when().
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Plant::query();

        // Búsqueda por texto (nombre o categoría)
        $query->when(! empty($filters['search']), function ($q) use ($filters) {
            $search = mb_strtolower(trim($filters['search']));
            $q->where(function ($sub) use ($search) {
                $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(category_type) LIKE ?', ["%{$search}%"]);
            });
        });

        // Filtro por categoría específica
        $query->when(! empty($filters['category_type']), function ($q) use ($filters) {
            $q->where('category_type', trim($filters['category_type']));
        });

        // Filtro por estado activo
        $query->when(isset($filters['active']) && $filters['active'] !== '', function ($q) use ($filters) {
            $active = filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($active !== null) {
                $q->where('active', $active);
            }
        });

        // Filtro por rango de precio mínimo
        $query->when(isset($filters['min_price']) && is_numeric($filters['min_price']), function ($q) use ($filters) {
            $q->where('price', '>=', (float) $filters['min_price']);
        });

        // Filtro por rango de precio máximo
        $query->when(isset($filters['max_price']) && is_numeric($filters['max_price']), function ($q) use ($filters) {
            $q->where('price', '<=', (float) $filters['max_price']);
        });

        // Ordenamiento dinámico
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Obtiene una planta por su identificador único.
     *
     * @throws ModelNotFoundException
     */
    public function getById(int $id): Plant
    {
        return Plant::findOrFail($id);
    }

    /**
     * Crea una nueva planta con subida opcional de imagen y registro en auditoría.
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
     * Actualiza una planta existente, gestionando el reemplazo o eliminación de imagen y registrando en auditoría.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data, ?UploadedFile $newImage = null): Plant
    {
        $plant = Plant::findOrFail($id);
        $oldData = $plant->toArray();

        return DB::transaction(function () use ($plant, $data, $newImage, $oldData) {
            $fileToUpload = $newImage ?? ($data['image'] ?? null) ?? ($data['image_url'] ?? null);

            if ($fileToUpload instanceof UploadedFile) {
                $data['image_url'] = FileUploadHelper::replace($fileToUpload, $plant->image_url, 'plants');
            } elseif (! empty($data['remove_image'])) {
                FileUploadHelper::delete($plant->image_url);
                $data['image_url'] = null;
            }

            unset($data['image'], $data['remove_image']);

            $plant->update($data);

            $this->logAudit('update', 'plants', $plant->id, $oldData, $plant->toArray());

            return $plant;
        });
    }

    /**
     * Elimina lógicamente una planta si no tiene cotizaciones asociadas.
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function delete(int $id): void
    {
        $plant = Plant::findOrFail($id);

        $quoteItemsCount = $plant->quoteItems()->count();
        if ($quoteItemsCount > 0) {
            throw ValidationException::withMessages([
                'plant' => ["No se puede eliminar la planta porque está asociada a {$quoteItemsCount} cotización(es) o presupuesto(s)."],
            ]);
        }

        $oldData = $plant->toArray();

        DB::transaction(function () use ($plant, $oldData) {
            $plant->delete();
            $this->logAudit('delete', 'plants', $plant->id, $oldData, null);
        });
    }

    /**
     * Registra un evento en la bitácora de auditoría.
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
