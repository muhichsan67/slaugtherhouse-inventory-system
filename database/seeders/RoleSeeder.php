<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('roles')->upsert([
            ['id' => 1, 'name' => 'admin', 'description' => 'Full access, user management', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'purchasing', 'description' => 'Suppliers and purchase orders', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'warehouse', 'description' => 'Receipts, deliveries, and stock', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'sales', 'description' => 'Customers and sales orders', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'finance', 'description' => 'Reports', 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['name', 'description', 'updated_at']);
    }
}
