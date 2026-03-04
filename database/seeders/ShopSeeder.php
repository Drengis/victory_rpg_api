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
        // Очищаем существующие магазины и товары
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \App\Models\Shop::truncate();
        \Illuminate\Support\Facades\DB::table('shop_items')->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $starterShop = \App\Models\Shop::create([
            'name' => 'Лавка для новичков',
            'description' => 'Здесь можно купить базовое снаряжение для первых приключений.',
            'min_level' => 1
        ]);

        $midShop = \App\Models\Shop::create([
            'name' => 'Снаряжение бывалого',
            'description' => 'Отличное снаряжение для тех, кто готов спускаться глубже. Доступно с 5 уровня.',
            'min_level' => 5
        ]);

        // Начальные предметы (цена до 50)
        $starterItems = \App\Models\Item::where('quality', 1)
            ->where('type', '!=', 'material')
            ->where('base_price', '<', 50)
            ->get();

        foreach ($starterItems as $item) {
            $starterShop->items()->attach($item->id, ['ilevel' => 1]);
        }

        // Средние предметы (цена 50 и выше)
        $midItems = \App\Models\Item::where('quality', 1)
            ->where('type', '!=', 'material')
            ->where('base_price', '>=', 50)
            ->get();

        foreach ($midItems as $item) {
            // Для "Лавки бывалого" делаем цену x3
            $priceOverride = $item->base_price * 3;
            
            $midShop->items()->attach($item->id, [
                'ilevel' => 1,
                'price_override' => $priceOverride
            ]);
        }
    }
}
