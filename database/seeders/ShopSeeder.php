<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $starterShop = \App\Models\Shop::updateOrCreate(
            ['name' => 'Лавка для новичков'],
            ['description' => 'Здесь можно купить базовое снаряжение для первых приключений.']
        );

        // Получаем все обычные предметы (quality = 1), которые не являются материалами
        $items = \App\Models\Item::where('quality', 1)
            ->where('type', '!=', 'material')
            ->get();

        foreach ($items as $item) {
            $starterShop->items()->syncWithoutDetaching([
                $item->id => ['ilevel' => 1]
            ]);
        }
    }
}
