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
    $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

    // ESTRATEGIA INTELIGENTE:
    // Borramos SOLO los tokens que tengan EL MISMO nombre del dispositivo actual.
    // Ej: Si me logueo en "Windows PC - Chrome", borra la sesión anterior de "Windows PC - Chrome"
    // pero deja viva la sesión de "iPhone - Safari".
    $user->tokens()->where('name', $request->device_name)->delete();

    // Creamos el nuevo token
    $token = $user->createToken($request->device_name)->plainTextToken;
        // ACTUALIZADO: Generar URL apuntando a public/uploads
        // Como guardamos solo el path relativo (ej: "perfil/foto.jpg"), concatenamos 'uploads/'
        $photoUrl = $user->profile_photo_path 
            ? url('uploads/' . $user->profile_photo_path) 
            : null; 

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo_url' => $photoUrl, // <--- URL lista para el src="" del frontend
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        // Validación de seguridad
        if ($token = $request->user()->currentAccessToken()) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ], 200);
    }
    
    public function me(Request $request)
    {
        $user = $request->user();
        
        $userData = $user->toArray();
        
        // ACTUALIZADO: Misma lógica para el endpoint /me
        $userData['photo_url'] = $user->profile_photo_path 
            ? url('uploads/' . $user->profile_photo_path) 
            : null;

        return response()->json($userData);
    }
}