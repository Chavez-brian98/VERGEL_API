<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Bitácora de intentos de acceso o modificación rechazados por las policies.
 *
 * Nunca se almacenan contraseñas ni tokens: solo datos de contexto del usuario,
 * la acción solicitada, el recurso afectado y el resultado del intento.
 */
class AccessAuditService
{
    /**
     * Registra en la bitácora (audit_log) un intento de acceso o modificación rechazado.
     */
    public function logDenied(Request $request): void
    {
        $user = $request->user();
        $employeeId = $user?->employee?->id;

        $context = [
            'result' => 'denied',
            'ability' => $this->resolveAbility($request),
            'resource' => $this->resolveResource($request),
            'record_id' => $this->resolveRecordId($request),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'role' => $user?->employee?->role?->role_name,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'ip_address' => $request->ip(),
        ];

        // Sin empleado asociado no existe employee_id válido para la bitácora.
        if (! $employeeId) {
            Log::warning('Intento de acceso denegado: '.json_encode($context));

            return;
        }

        try {
            AuditLog::create([
                'employee_id' => $employeeId,
                'affected_table' => $context['resource'],
                'affected_record_id' => $context['record_id'] ?? 0,
                'action' => 'denied',
                'old_data' => null,
                'new_data' => $context,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar el intento denegado en la bitácora: '.$e->getMessage());
        }
    }

    /**
     * Determina la acción que el usuario intentó ejecutar.
     */
    private function resolveAbility(Request $request): string
    {
        $action = (string) ($request->route()?->getActionMethod() ?? '');

        return match ($action) {
            'index', 'show', 'planHistory' => 'view',
            'store' => 'create',
            'update', 'updateStatus', 'updatePlan' => 'update',
            'destroy' => 'delete',
            default => 'unknown',
        };
    }

    /**
     * Determina el recurso afectado por el intento (por ejemplo: customers).
     */
    private function resolveResource(Request $request): string
    {
        $path = str_replace('api/v1/', '', $request->path());
        $segments = explode('/', trim($path, '/'));

        return $segments[0] !== '' ? $segments[0] : 'unknown';
    }

    /**
     * Determina el identificador del registro afectado, si la ruta lo incluye.
     */
    private function resolveRecordId(Request $request): ?int
    {
        $parameters = $request->route()?->parameters() ?? [];

        foreach (['customer', 'id'] as $key) {
            if (isset($parameters[$key]) && is_numeric($parameters[$key])) {
                return (int) $parameters[$key];
            }
        }

        return null;
    }
}
