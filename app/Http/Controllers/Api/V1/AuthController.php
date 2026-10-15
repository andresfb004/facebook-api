<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Autenticación
 *
 * Registro, inicio y cierre de sesión. Los endpoints protegidos de la API
 * requieren el token que devuelven el registro o el login.
 */
class AuthController extends Controller
{
    /**
     * Registrar usuario
     *
     * Crea una cuenta nueva y devuelve un token para usar en las rutas protegidas.
     *
     * @unauthenticated
     *
     * @bodyParam name string required Nombre del usuario. Example: Andrés Forero
     * @bodyParam email string required Correo del usuario. Debe ser único. Example: andres@test.com
     * @bodyParam password string required Contraseña de mínimo 8 caracteres. Example: password123
     * @bodyParam password_confirmation string required Debe ser igual a password. Example: password123
     *
     * @response 201 {"user": {"id": 1, "name": "Andrés Forero", "email": "andres@test.com", "created_at": "2026-10-15T05:42:29.000000Z", "updated_at": "2026-10-15T05:42:29.000000Z"}, "token": "1|y98K2zNG2YIUZ6e2yRIbbOMypLgh3m87tqnsr8GP800a92ef"}
     * @response 422 scenario="Email ya registrado" {"message": "The email has already been taken.", "errors": {"email": ["The email has already been taken."]}}
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Iniciar sesión
     *
     * Valida el correo y la contraseña y devuelve un token nuevo.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Correo del usuario. Example: andres@test.com
     * @bodyParam password string required Contraseña del usuario. Example: password123
     *
     * @response 200 {"user": {"id": 1, "name": "Andrés Forero", "email": "andres@test.com", "email_verified_at": null, "created_at": "2026-10-15T05:42:29.000000Z", "updated_at": "2026-10-15T05:42:29.000000Z"}, "token": "2|AJ2TFdxsSS73BINWGOXnmm1zZKQGqMjwGYO2U5CF2e8a7843"}
     * @response 401 scenario="Credenciales incorrectas" {"message": "Credenciales incorrectas"}
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * Cerrar sesión
     *
     * Elimina el token con el que se hizo la petición. Después hay que volver a iniciar sesión.
     *
     * @authenticated
     *
     * @response 204 scenario="Sesión cerrada"
     * @response 401 scenario="Sin token" {"message": "Unauthenticated."}
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}