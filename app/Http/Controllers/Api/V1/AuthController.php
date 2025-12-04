<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // 1. Buscamos al usuario (ya sabemos que existe por la validación del Request)
        $user = User::where('email', $request->email)->first();

        // 2. Verificamos la contraseña
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // 3. (Opcional) Borrar tokens anteriores si quieres sesión única
        // $user->tokens()->delete();

        // 4. Crear el token de acceso (Sanctum)
        // Usamos el device_name que envía Vue para saber qué dispositivo es
        $token = $user->createToken($request->device_name)->plainTextToken;

        // 5. Retornar respuesta JSON estandarizada
        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        // Revoca el token actual que se usó para la petición
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ], 200);
    }
    
    // Método extra para obtener usuario actual (útil para recargar la página en Vue)
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}