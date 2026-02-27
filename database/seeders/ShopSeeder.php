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

        $midShop = \App\Models\Shop::updateOrCreate(
            ['name' => 'Снаряжение бывалого'],
            ['description' => 'Отличное снаряжение для тех, кто готов спускаться глубже.']
        );

        // Начальные предметы (цена до 50)
        $starterItems = \App\Models\Item::where('quality', 1)
            ->where('type', '!=', 'material')
            ->where('base_price', '<', 50)
            ->get();

        foreach ($starterItems as $item) {
            $starterShop->items()->syncWithoutDetaching([
                $item->id => ['ilevel' => 1]
            ]);
        }

        // Средние предметы (цена 50 и выше)
        $midItems = \App\Models\Item::where('quality', 1)
            ->where('type', '!=', 'material')
            ->where('base_price', '>=', 50)
            ->get();

        foreach ($midItems as $item) {
            $midShop->items()->syncWithoutDetaching([
                $item->id => ['ilevel' => 1] // Уровень продаваемого предмета можно настроить
            ]);
    
    
        }

        // Удаляем старые магазины, если они были
        $validShopIds = [$starterShop->id, $midShop->id];
        \App\Models\Shop::whereNotIn('id', $validShopIds)->delete();
    }
}
