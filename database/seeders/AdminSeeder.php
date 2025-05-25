<?php

namespace Database\Seeders;

use App\RoleManager;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'first_name' => 'Open',
            'last_name' => 'Policy',
            'phone' => '000000000000',
            'postal_code' => 'K6V 4W6',
            'email' => 'dev.openpolicy@gmail.com',
            'password' => Hash::make('openpolicy1o1oAdmin'),
            'role' => RoleManager::ADMIN,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}
