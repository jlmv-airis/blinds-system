<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MvpSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@blinds-system.com'],
            [
                'name' => 'Admin MVP',
                'password' => Hash::make('ChangeMe123!'),
                'role' => 'admin',
            ]
        );
    }
}
