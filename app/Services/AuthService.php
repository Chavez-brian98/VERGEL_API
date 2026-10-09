<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Autentica a un usuario con sus credenciales y genera un token de acceso.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, access_token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $user->load('employee'); //agrega la información del empleado al usuario

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $token,
        ];
    }

    /**
     * Revoca o elimina el token de acceso actual del usuario autenticado.
     */
    public function logout(User $user): void
    {
        /** @var PersonalAccessToken|null $token  ' Da la "pista" al editor para que entienda el tipo real '*/
        $token = $user->currentAccessToken();

        $token?->delete();
    }
}
