<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitialGameDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Предметы
        // Оружие
        $sword = \App\Models\Item::updateOrCreate(
            ['name' => 'Старый меч'],
            [
                'type' => 'weapon',
                'quality' => 1,
                'base_price' => 20,
                'min_damage' => 2,
                'max_damage' => 4,
                'strength' => 1,
                'scaling_factor' => 0.1,
                'required_class' => 'воин'
            ]
        );

        $bow = \App\Models\Item::updateOrCreate(
            ['name' => 'Лук с трещиной'],
            [
                'type' => 'weapon',
                'quality' => 1,
                'base_price' => 20,
                'min_damage' => 2,
                'max_damage' => 4,
                'agility' => 1,
                'scaling_factor' => 0.1,
                'required_class' => 'лучник'
            ]
        );

        $staff = \App\Models\Item::updateOrCreate(
            ['name' => 'Посох без маны'],
            [
                'type' => 'weapon',
                'quality' => 1,
                'base_price' => 20,
                'min_damage' => 2,
                'max_damage' => 3,
                'intelligence' => 1,
                'scaling_factor' => 0.1,
                'required_class' => 'маг'
            ]
        );

        // Броня и аксессуары (Классовые)
        $helmetWarrior = \App\Models\Item::updateOrCreate(
            ['name' => 'Ржавый шлем'],
            [
                'type' => 'head',
                'quality' => 1,
                'base_price' => 15,
                'constitution' => 1,
                'scaling_factor' => 0.08,
                'required_class' => 'воин'
            ]
        );

        $helmetArcher = \App\Models\Item::updateOrCreate(
            ['name' => 'Кожаный капюшон'],
            [
                'type' => 'head',
                'quality' => 1,
                'base_price' => 15,
                'agility' => 1,
                'scaling_factor' => 0.08,
                'required_class' => 'лучник'
            ]
        );

        $helmetMage = \App\Models\Item::updateOrCreate(
            ['name' => 'Тканевая повязка'],
            [
                'type' => 'head',
                'quality' => 1,
                'base_price' => 15,
                'intelligence' => 1,
                'scaling_factor' => 0.08,
                'required_class' => 'маг'
            ]
        );

        $chestWarrior = \App\Models\Item::updateOrCreate(
            ['name' => 'Потертая куртка'],
            [
                'type' => 'chest',
                'quality' => 1,
                'base_price' => 25,
                'constitution' => 2,
                'scaling_factor' => 0.08,
                'required_class' => 'воин'
            ]
        );

        $chestArcher = \App\Models\Item::updateOrCreate(
            ['name' => 'Дорожный плащ'],
            [
                'type' => 'chest',
                'quality' => 1,
                'base_price' => 25,
                'agility' => 2,
                'scaling_factor' => 0.08,
                'required_class' => 'лучник'
            ]
        );

        $chestMage = \App\Models\Item::updateOrCreate(
            ['name' => 'Ученическая роба'],
            [
                'type' => 'chest',
                'quality' => 1,
                'base_price' => 25,
                'intelligence' => 2,
                'scaling_factor' => 0.08,
                'required_class' => 'маг'
            ]
        );

        $hands = \App\Models\Item::updateOrCreate(
            ['name' => 'Грязные перчатки'],
            [
                'type' => 'hands',
                'quality' => 1,
                'base_price' => 10,
                'agility' => 1,
                'scaling_factor' => 0.08
            ]
        );

        $legs = \App\Models\Item::updateOrCreate(
            ['name' => 'Дырявые штаны'],
            [
                'type' => 'legs',
                'quality' => 1,
                'base_price' => 15,
                'agility' => 1,
                'scaling_factor' => 0.08
            ]
        );

        $feet = \App\Models\Item::updateOrCreate(
            ['name' => 'Старые сапоги'],
            [
                'type' => 'feet',
                'quality' => 1,
                'base_price' => 10,
                'agility' => 1,
                'scaling_factor' => 0.08
            ]
        );

        $neck = \App\Models\Item::updateOrCreate(
            ['name' => 'Простой амулет'],
            [
                'type' => 'neck',
                'quality' => 1,
                'base_price' => 30,
                'luck' => 1,
                'scaling_factor' => 0.1
            ]
        );

        $ring = \App\Models\Item::updateOrCreate(
            ['name' => 'Медное кольцо'],
            [
                'type' => 'ring',
                'quality' => 1,
                'base_price' => 25,
                'intelligence' => 1,
                'scaling_factor' => 0.1
            ]
        );

        $belt = \App\Models\Item::updateOrCreate(
            ['name' => 'Веревочный пояс'],
            [
                'type' => 'belt',
                'quality' => 1,
                'base_price' => 8,
                'constitution' => 1,
                'scaling_factor' => 0.05
            ]
        );

        $trinket = \App\Models\Item::updateOrCreate(
            ['name' => 'Странный камушек'],
            [
                'type' => 'trinket',
                'quality' => 1,
                'base_price' => 40,
                'luck' => 2,
                'scaling_factor' => 0.12
            ]
        );

        $tail = \App\Models\Item::updateOrCreate(
            ['name' => 'Хвост крысы'],
            [
                'type' => 'material',
                'quality' => 1,
                'base_price' => 2,
                'scaling_factor' => 0.05
            ]
        );

        // 2. Таблица лута
        $lootTable = \App\Models\LootTable::updateOrCreate(['name' => 'Начальный лут']);
        
        \App\Models\LootItem::updateOrCreate(
            ['loot_table_id' => $lootTable->id, 'item_id' => $tail->id],
            [
                'chance' => 60.0,
                'min_quantity' => 1,
                'max_quantity' => 2
            ]
        );

        // Редкий шанс на меч с крысы (для теста)
        \App\Models\LootItem::updateOrCreate(
            ['loot_table_id' => $lootTable->id, 'item_id' => $sword->id],
            [
                'chance' => 5.0,
                'min_quantity' => 1,
                'max_quantity' => 1
            ]
        );

        // 3. Монстр
        \App\Models\Enemy::updateOrCreate(
            ['name' => 'Крыса'],
            [
                'level' => 1,
                'strength' => 3,
                'agility' => 5,
                'constitution' => 4,
                'intelligence' => 1,
                'luck' => 2,
                'min_damage' => 1,
                'max_damage' => 2,
                'base_experience' => 20,
                'base_gold' => 10,
                'scaling_factor' => 0.1,
                'loot_table_id' => $lootTable->id
            ]
        );
    }
}
