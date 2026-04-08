<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * GET /admin/usuarios
     * Lista todos los usuarios con su rol y permiso de dashboard.
     */
    public function index()
    {
        $users = User::with('roles', 'permissions')
            ->get()
            ->map(fn($u) => $this->formatUser($u));

        return response()->json($users);
    }

    /**
     * POST /admin/usuarios
     * Crear nuevo usuario y asignarle un rol.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(6)],
            'role'     => 'required|string|in:Super Admin,Direccion,Secretaria,Usuario',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        return response()->json($this->formatUser($user->load('roles', 'permissions')), 201);
    }

    /**
     * PUT /admin/usuarios/{id}
     * Actualizar datos y/o rol de un usuario.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => "sometimes|email|unique:users,email,{$id}",
            'password' => ['sometimes', 'nullable', Password::min(6)],
            'role'     => 'sometimes|string|in:Super Admin,Direccion,Secretaria,Usuario',
        ]);

        $user->fill(array_filter([
            'name'  => $data['name']  ?? null,
            'email' => $data['email'] ?? null,
        ]));

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json($this->formatUser($user->load('roles', 'permissions')));
    }

    /**
     * DELETE /admin/usuarios/{id}
     * Eliminar un usuario (no puede eliminarse a sí mismo).
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    /**
     * PUT /admin/usuarios/{id}/permisos
     * Toggle del permiso ver-dashboard para un usuario.
     */
    public function togglePermiso(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $permiso = Permission::where('name', 'ver-dashboard')
            ->where('guard_name', 'web')
            ->firstOrFail();

        if ($user->hasPermissionTo($permiso)) {
            $user->revokePermissionTo($permiso);
            $tiene = false;
        } else {
            $user->givePermissionTo($permiso);
            $tiene = true;
        }

        return response()->json([
            'message'         => $tiene ? 'Permiso otorgado.' : 'Permiso revocado.',
            'ver_dashboard'   => $tiene,
        ]);
    }

    // ─── Helper ────────────────────────────────────────────────────────────────

    private function formatUser(User $user): array
    {
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => $user->getRoleNames()->first() ?? 'Sin rol',
            'ver_dashboard' => $user->hasPermissionTo('ver-dashboard') || $user->hasRole('Super Admin'),
            'photo_url'     => $user->profile_photo_path ? url('uploads/' . $user->profile_photo_path) : null,
        ];
    }
}
