<?php

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // --- Debug temporal para ver si llega la request
\Log::info('Llegó a updateProfile', [
    'user_id' => optional($request->user())->id,
    'all_request' => $request->all(),
    'files' => $request->file()
]);


        // --- Validaciones
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 
                'email', 
                Rule::unique('users')->ignore($user->id),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp,avif', 'max:2048'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        // --- Manejo de foto
        if ($request->hasFile('photo')) {
            // Borrar foto anterior
            if ($user->profile_photo_path && Storage::disk('uploads_public')->exists($user->profile_photo_path)) {
                Storage::disk('uploads_public')->delete($user->profile_photo_path);
            }

            $path = $request->file('photo')->store('perfil', 'uploads_public');
            $user->profile_photo_path = $path;
        }

        // --- Actualizar datos
        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // --- URL de foto
        $userData = $user->toArray();
        $userData['photo_url'] = $user->profile_photo_path 
            ? url('uploads/' . $user->profile_photo_path) 
            : null;

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user' => $userData
        ], 200);
    }
}
