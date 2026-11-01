<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = '2026-10-19 08:00:00';
        $password = Hash::make('password');
        $users = [
            [1, 1, 'Admin RPH', 'admin@rph.test'],
            [2, 2, 'Budi Purchasing', 'purchasing@rph.test'],
            [3, 3, 'Wawan Warehouse', 'warehouse@rph.test'],
            [4, 4, 'Sari Sales', 'sales@rph.test'],
            [5, 5, 'Fina Finance', 'finance@rph.test'],
        ];

        $rows = [];
        foreach ($users as [$id, $roleId, $name, $email]) {
            $rows[] = [
                'id' => $id,
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'email_verified_at' => $now,
                'password' => $password,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('users')->upsert(
            $rows,
            ['email'],
            ['role_id', 'name', 'email_verified_at', 'password', 'is_active', 'updated_at'],
        );
    }
}
