<?php

namespace Database\Seeders;

use App\Models\ProductModel;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 50 productos normales, 30 en oferta y 20 en subasta
        //ProductModel::factory()->count(50)->create();
        //ProductModel::factory()->count(30)->onOffer()->create();
        ProductModel::factory()->count(20)->auction()->create();
    }
}