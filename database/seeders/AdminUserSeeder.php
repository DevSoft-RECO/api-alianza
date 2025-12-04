<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificamos si ya existe para no duplicarlo al correr seeds
        if (!User::where('email', 'admin@admin.com')->exists()) {
            User::create([
                'name' => 'Administrador Principal',
                'email' => 'admin@admin.com',
                'password' => Hash::make('1234'), // Contraseña segura
                // Aquí podrías agregar 'role' => 'admin' si modificas tu migración luego
            ]);
            echo "Usuario Admin creado: admin@admin.com / password123\n";
        }
    }
}