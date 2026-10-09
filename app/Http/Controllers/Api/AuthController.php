<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Iniciar sesión.
     *
     * Autentica las credenciales del usuario y genera un token de acceso personal (Sanctum) para consumir los endpoints protegidos.
     *
     * @group Autenticación
     *
     * @subgroup Sesión
     *
     * @unauthenticated
     *
     * @bodyParam email string required Correo electrónico del usuario. Example: admin@vergel.com
     * @bodyParam password string required Contraseña de acceso. Example: password123
     *
     * @response status=200 {
     *   "message": "Inicio de sesión exitoso.",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Administrador Vergel",
     *       "email": "admin@vergel.com",
     *       "employee_id": 1,
     *       "created_at": "2026-10-08T22:58:33.000000Z",
     *       "updated_at": "2026-10-08T22:58:33.000000Z",
     *       "employee": {
     *         "id": 1,
     *         "full_name": "Carlos Mendoza (Administración)",
     *         "role_id": 1,
     *         "dui": null,
     *         "address": null,
     *         "municipality": null,
     *         "department": null,
     *         "birth_date": null,
     *         "hire_date": null,
     *         "phone": null,
     *         "active": true,
     *         "created_at": "2026-10-08T22:58:33.000000Z",
     *         "updated_at": "2026-10-08T22:58:33.000000Z"
     *       }
     *     },
     *     "access_token": "1|hDBQYufM7...",
     *     "token_type": "Bearer"
     *   }
     * }
     * @response status=422 {
     *   "message": "Las credenciales proporcionadas son incorrectas.",
     *   "errors": {
     *     "email": [
     *       "Las credenciales proporcionadas son incorrectas."
     *     ]
     *   }
     * }
     * @response status=500 {
     *   "message": "Error interno del servidor"
     * }
     */
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());

            return response()->json([
                'message' => 'Inicio de sesión exitoso.',
                'data' => [
                    'user' => $result['user'],
                    'access_token' => $result['access_token'],
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en login: '.$e->getMessage());

            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Cerrar sesión.
     *
     * Invalida y elimina el token de acceso actual del usuario autenticado.
     *
     * @group Autenticación
     *
     * @subgroup Sesión
     *
     * @authenticated
     *
     * @response status=200 {
     *   "message": "Sesión cerrada exitosamente."
     * }
     * @response status=401 {
     *   "message": "No autenticado"
     * }
     * @response status=500 {
     *   "message": "Error interno al cerrar sesión"
     * }
     */
    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());

            return response()->json([
                'message' => 'Sesión cerrada exitosamente.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error en logout: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al cerrar sesión'], 500);
        }
    }
}
