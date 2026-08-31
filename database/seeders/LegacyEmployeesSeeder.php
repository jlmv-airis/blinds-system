<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios reales de la empresa, migrados desde el comando legacy
 * app/Console/Commands/CreateUsersFromTemplate.php — mismas contraseñas
 * a propósito (decisión del usuario, 2026-08-26), aunque ya estaban
 * expuestas en el código fuente legacy. Rol por defecto: "user" (no
 * admin) para todos — se asume el mínimo privilegio hasta que se
 * confirme quién necesita permisos de administrador.
 */
class LegacyEmployeesSeeder extends Seeder
{
    public function run()
    {
        $users = [
            ['email' => 'ena.dela.torre@blinds-system.com', 'password' => 'Blind0032', 'name' => 'Ena de la Torre'],
            ['email' => 'direccion-comercial@blinds-system.com', 'password' => 'Blind0033', 'name' => 'Direccion Comercial'],
            ['email' => 'administracion@blinds-system.com', 'password' => 'Blind0034', 'name' => 'Administracion'],
            ['email' => 'laura.rojas@blinds-system.com', 'password' => 'Blind0035', 'name' => 'Laura Rojas'],
            ['email' => 'pamela.lanson@blinds-system.com', 'password' => 'Blind0036', 'name' => 'Pamela Lanson'],
            ['email' => 'produccion@blinds-system.com', 'password' => 'Blind0037', 'name' => 'Produccion'],
            ['email' => 'compras@blinds-system.com', 'password' => 'Blind0038', 'name' => 'Compras'],
            ['email' => 'embarques@blinds-system.com', 'password' => 'Blind0039', 'name' => 'Embarques'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($u['password']),
                    'role' => 'user',
                ]
            );
        }
    }
}
