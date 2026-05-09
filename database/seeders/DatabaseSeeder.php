<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun Admin
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role'     => 'admin',
            ]
        );

        // Akun Siswa
        User::firstOrCreate(
            ['username' => 'siswa'],
            [
                'name'     => 'Siswa Demo',
                'username' => 'siswa',
                'password' => Hash::make('siswa'),
                'role'     => 'siswa',
            ]
        );
    }
}
