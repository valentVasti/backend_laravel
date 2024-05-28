<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\MaxTimeTransaction;
use App\Models\Mesin;
use App\Models\Product;
use App\Models\ThresholdTime;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin'),
            'phone_num' => '081234567890',
            'role' => 'ADMIN'
        ]);

        MaxTimeTransaction::create([
            'max_time' => '00:10:00'
        ]);

        ThresholdTime::create([
            'threshold_time' => '5'
        ]);

        Mesin::create([
            'kode_mesin' => 'P1',
            'jenis_mesin' => 'PENGERING',
            'identifier' => '111111',
            'durasi_penggunaan' => '60',
            'status_maintenance' => '0',
        ]);

        Mesin::create([
            'kode_mesin' => 'P2',
            'jenis_mesin' => 'PENGERING',
            'identifier' => '2222222',
            'durasi_penggunaan' => '60',
            'status_maintenance' => '0',
        ]);

        Mesin::create([
            'kode_mesin' => 'C1',
            'jenis_mesin' => 'PENCUCI',
            'identifier' => '333333',
            'durasi_penggunaan' => '60',
            'status_maintenance' => '0',
        ]);

        Mesin::create([
            'kode_mesin' => 'C2',
            'jenis_mesin' => 'PENCUCI',
            'identifier' => '444444',
            'durasi_penggunaan' => '60',
            'status_maintenance' => '0',
        ]);

        Product::create([
            'product_name' => 'Cuci',
            'price' => '10000',
            'status' => '1'
        ]);

        Product::create([
            'product_name' => 'Kering',
            'price' => '10000',
            'status' => '1'
        ]);
    }
}
