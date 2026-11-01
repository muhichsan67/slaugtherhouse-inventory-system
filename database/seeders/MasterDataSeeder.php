<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    private const CREATED_AT = '2026-10-19 08:00:00';

    public function run(): void
    {
        DB::table('categories')->upsert([
            $this->timestamps(['id' => 1, 'name' => 'Live Animals', 'description' => 'Cattle, goats and other live stock']),
            $this->timestamps(['id' => 2, 'name' => 'Carcass', 'description' => 'Whole or half carcass']),
            $this->timestamps(['id' => 3, 'name' => 'Meat Cuts', 'description' => 'Primal and retail cuts']),
            $this->timestamps(['id' => 4, 'name' => 'By-products', 'description' => 'Offal, bones, hides']),
            $this->timestamps(['id' => 5, 'name' => 'Supplies', 'description' => 'Packaging and consumables']),
        ], ['id'], ['name', 'description', 'updated_at']);

        DB::table('units')->upsert([
            $this->timestamps(['id' => 1, 'code' => 'KG', 'name' => 'Kilogram']),
            $this->timestamps(['id' => 2, 'code' => 'HEAD', 'name' => 'Head (per animal)']),
            $this->timestamps(['id' => 3, 'code' => 'PCS', 'name' => 'Piece']),
        ], ['id'], ['code', 'name', 'updated_at']);

        $items = [
            [1, 1, 2, 'LIV-CAT', 'Live Cattle', 'live_animal', 0],
            [2, 1, 2, 'LIV-GOT', 'Live Goat', 'live_animal', 0],
            [3, 2, 1, 'CAR-BEF', 'Beef Carcass', 'carcass', 110000],
            [4, 2, 1, 'CAR-GOT', 'Goat Carcass', 'carcass', 125000],
            [5, 3, 1, 'CUT-BEF-TEN', 'Beef Tenderloin', 'cut', 230000],
            [6, 3, 1, 'CUT-BEF-SIR', 'Beef Sirloin', 'cut', 170000],
            [7, 3, 1, 'CUT-BEF-RIB', 'Beef Short Ribs', 'cut', 120000],
            [8, 3, 1, 'CUT-BEF-MIN', 'Minced Beef', 'cut', 115000],
            [9, 4, 1, 'BYP-BEF-LIV', 'Beef Liver', 'by_product', 60000],
            [10, 4, 1, 'BYP-BEF-TRP', 'Beef Tripe', 'by_product', 45000],
            [11, 4, 1, 'BYP-BEF-BON', 'Beef Bones', 'by_product', 15000],
            [12, 5, 3, 'SUP-VAC-BAG', 'Vacuum Bag', 'supply', 500],
        ];
        DB::table('items')->upsert(array_map(fn (array $item): array => $this->timestamps([
            'id' => $item[0],
            'category_id' => $item[1],
            'unit_id' => $item[2],
            'sku' => $item[3],
            'name' => $item[4],
            'item_type' => $item[5],
            'sale_price' => $item[6],
            'is_active' => true,
        ]), $items), ['id'], ['category_id', 'unit_id', 'sku', 'name', 'item_type', 'sale_price', 'is_active', 'updated_at']);

        DB::table('warehouses')->upsert([
            $this->timestamps(['id' => 1, 'code' => 'WH-01', 'name' => 'Cold Storage', 'address' => 'Main plant, building A', 'is_active' => true]),
            $this->timestamps(['id' => 2, 'code' => 'WH-02', 'name' => 'Dry Storage', 'address' => 'Main plant, building B', 'is_active' => true]),
        ], ['id'], ['code', 'name', 'address', 'is_active', 'updated_at']);

        DB::table('partners')->upsert([
            $this->timestamps(['id' => 1, 'type' => 'supplier', 'name' => 'PT Sumber Ternak Jaya', 'phone' => '021-5550101', 'address' => 'Jl. Peternakan No. 12, Bogor', 'is_active' => true]),
            $this->timestamps(['id' => 2, 'type' => 'supplier', 'name' => 'CV Berkah Daging Nusantara', 'phone' => '021-5550102', 'address' => 'Jl. Raya Cibinong No. 45, Bogor', 'is_active' => true]),
            $this->timestamps(['id' => 3, 'type' => 'customer', 'name' => 'Toko Daging Segar Pasar Minggu', 'phone' => '021-5550201', 'address' => 'Pasar Minggu, Jakarta Selatan', 'is_active' => true]),
            $this->timestamps(['id' => 4, 'type' => 'customer', 'name' => 'Restoran Sate Senayan', 'phone' => '021-5550202', 'address' => 'Senayan, Jakarta Pusat', 'is_active' => true]),
            $this->timestamps(['id' => 5, 'type' => 'customer', 'name' => 'Catering Dapur Sehat', 'phone' => '021-5550203', 'address' => 'Tebet, Jakarta Selatan', 'is_active' => true]),
        ], ['id'], ['type', 'name', 'phone', 'address', 'is_active', 'updated_at']);

    }

    private function timestamps(array $row): array
    {
        return [
            ...$row,
            'created_at' => self::CREATED_AT,
            'updated_at' => self::CREATED_AT,
        ];
    }
}
