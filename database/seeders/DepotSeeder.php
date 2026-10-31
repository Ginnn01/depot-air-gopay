<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DepotSeeder extends Seeder
{
    public function run(): void
    {
       User::updateOrCreate(
    ['email' => 'rahma@depot.test'],
    ['name' => 'Buk Rahma', 'password' => Hash::make('password'), 'role' => 'pemilik']
);

User::updateOrCreate(
    ['email' => 'asnawi@depot.test'],
    ['name' => 'Asnawi', 'password' => Hash::make('password'), 'role' => 'kasir']
);
       

        // Harga contoh, ganti sesuai harga di depot
        $products = [
            ['name' => 'Isi Ulang Galon', 'price' => 5000],
            ['name' => 'Galon Baru + Isi', 'price' => 45000],
            ['name' => 'Ongkos Antar', 'price' => 2000],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['name' => $product['name']], $product);
        }
    }
}
