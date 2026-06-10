<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosRolesSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'name' => 'Administrador',
                'email' => 'admin@erpsolis.com',
                'password' => 'Admin12345',
                'rol' => 'Administrador',
            ],
            [
                'name' => 'Usuario Directivo',
                'email' => 'directivo@erpsolis.com',
                'password' => 'Directivo12345',
                'rol' => 'Directivo',
            ],
            [
                'name' => 'Usuario Operativo',
                'email' => 'operativo@erpsolis.com',
                'password' => 'Operativo12345',
                'rol' => 'Operativo',
            ],
        ];

        foreach ($usuarios as $usuario) {
            User::updateOrCreate(
                ['email' => $usuario['email']],
                [
                    'name' => $usuario['name'],
                    'password' => Hash::make($usuario['password']),
                    'rol' => $usuario['rol'],
                ]
            );
        }
    }
}
