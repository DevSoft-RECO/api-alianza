<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. Crear Permisos ---
        Permission::firstOrCreate(['name' => 'ver-dashboard', 'guard_name' => 'web']);

        // --- 2. Crear Roles (Idempotente con firstOrCreate) ---
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Direccion', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Secretaria', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Usuario', 'guard_name' => 'web']);

        // El "Super Admin" no necesita permisos explícitos gracias al Gate::before configurado en AppServiceProvider

        // --- 3. Asignar Super Admin al primer usuario de la tabla ---
        $superAdmin = User::first();
        if ($superAdmin && !$superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole($superAdminRole);
        }
    }
}
