<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        for ($i = 1; $i <= 20; $i++) {
            Product::create([
                'name' => 'Product ' . $i,
                'description' => Str::random(50),
                'price' => rand(10, 500) + (rand(0, 99) / 100),
                'image_url' => 'https://via.placeholder.com/150?text=Product+' . $i,
                'stock_quantity' => rand(1, 100),
                'user_id' => $user->id,
            ]);
        }
    }
}
