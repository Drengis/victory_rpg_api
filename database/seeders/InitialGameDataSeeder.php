<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\LootTable;
use App\Models\LootItem;
use App\Models\Enemy;

class InitialGameDataSeeder extends Seeder
{
    public function run(): void
    {
        // Коллекции для хранения ID актуальных записей
        $itemIds = collect();
        $lootTableIds = collect();
        $enemyIds = collect();
        $lootItemIds = collect();

        // --- 1. ПРЕДМЕТЫ ---

        // Вспомогательная функция, чтобы не писать push() каждый раз
        $createItem = function ($name, $data) use (&$itemIds) {
            $item = Item::updateOrCreate(['name' => $name], $data);
            $itemIds->push($item->id);
            return $item;
        };

        // Начальное оружие
        $sword = $createItem('Старый меч', ['type' => 'weapon', 'quality' => 1, 'base_price' => 20, 'min_damage' => 7, 'max_damage' => 12, 'strength' => 1, 'scaling_factor' => 0.1, 'required_class' => 'воин']);
        $bow = $createItem('Лук с трещиной', ['type' => 'weapon', 'quality' => 1, 'base_price' => 20, 'min_damage' => 7, 'max_damage' => 12, 'agility' => 1, 'scaling_factor' => 0.1, 'required_class' => 'лучник']);
        $staff = $createItem('Посох без маны', ['type' => 'weapon', 'quality' => 1, 'base_price' => 20, 'min_damage' => 7, 'max_damage' => 12, 'intelligence' => 1, 'scaling_factor' => 0.1, 'required_class' => 'маг']);

        // Броня и аксессуары
        $helmetWarrior = $createItem('Ржавый шлем', ['type' => 'head', 'quality' => 1, 'base_price' => 15, 'constitution' => 1, 'scaling_factor' => 0.08, 'required_class' => 'воин']);
        $helmetArcher = $createItem('Кожаный капюшон', ['type' => 'head', 'quality' => 1, 'base_price' => 15, 'agility' => 1, 'scaling_factor' => 0.08, 'required_class' => 'лучник']);
        $helmetMage = $createItem('Тканевая повязка', ['type' => 'head', 'quality' => 1, 'base_price' => 15, 'intelligence' => 1, 'scaling_factor' => 0.08, 'required_class' => 'маг']);
        $chestWarrior = $createItem('Потертая куртка', ['type' => 'chest', 'quality' => 1, 'base_price' => 25, 'constitution' => 2, 'scaling_factor' => 0.08, 'required_class' => 'воин', 'armor' => 4]);
        $chestArcher = $createItem('Дорожный плащ', ['type' => 'chest', 'quality' => 1, 'base_price' => 25, 'agility' => 2, 'scaling_factor' => 0.08, 'required_class' => 'лучник', 'armor' => 3]);
        $chestMage = $createItem('Ученическая роба', ['type' => 'chest', 'quality' => 1, 'base_price' => 25, 'intelligence' => 2, 'scaling_factor' => 0.08, 'required_class' => 'маг', 'armor' => 2]);

        $hands = $createItem('Грязные перчатки', ['type' => 'hands', 'quality' => 1, 'base_price' => 10, 'agility' => 1, 'scaling_factor' => 0.08]);
        $legs = $createItem('Дырявые штаны', ['type' => 'legs', 'quality' => 1, 'base_price' => 15, 'agility' => 1, 'scaling_factor' => 0.08]);
        $feet = $createItem('Старые сапоги', ['type' => 'feet', 'quality' => 1, 'base_price' => 10, 'agility' => 1, 'scaling_factor' => 0.08]);
        $neck = $createItem('Простой амулет', ['type' => 'neck', 'quality' => 1, 'base_price' => 30, 'luck' => 1, 'scaling_factor' => 0.1]);
        $ring = $createItem('Медное кольцо', ['type' => 'ring', 'quality' => 1, 'base_price' => 25, 'intelligence' => 1, 'scaling_factor' => 0.1]);
        $belt = $createItem('Веревочный пояс', ['type' => 'belt', 'quality' => 1, 'base_price' => 8, 'constitution' => 1, 'scaling_factor' => 0.05]);
        $trinket = $createItem('Странный камушек', ['type' => 'trinket', 'quality' => 1, 'base_price' => 40, 'luck' => 2, 'scaling_factor' => 0.12]);

        // Среднее снаряжение
        $steelSword = $createItem('Закаленный стальной меч', ['type' => 'weapon', 'quality' => 1, 'base_price' => 100, 'min_damage' => 12, 'max_damage' => 18, 'strength' => 3, 'scaling_factor' => 0.15, 'required_class' => 'воин']);
        $chainHelmet = $createItem('Кольчужный шлем', ['type' => 'head', 'quality' => 1, 'base_price' => 80, 'constitution' => 2, 'scaling_factor' => 0.1, 'required_class' => 'воин', 'armor' => 2]);
        $lamellarChest = $createItem('Ламеллярный доспех', ['type' => 'chest', 'quality' => 1, 'base_price' => 120, 'constitution' => 4, 'scaling_factor' => 0.1, 'required_class' => 'воин', 'armor' => 8]);
        $chainLegs = $createItem('Кольчужные поножи', ['type' => 'legs', 'quality' => 1, 'base_price' => 90, 'strength' => 2, 'scaling_factor' => 0.1, 'armor' => 4]);
        $ironHands = $createItem('Железные рукавицы', ['type' => 'hands', 'quality' => 1, 'base_price' => 70, 'constitution' => 1, 'scaling_factor' => 0.1, 'armor' => 3]);
        $heavyBoots = $createItem('Тяжелые сапоги', ['type' => 'feet', 'quality' => 1, 'base_price' => 70, 'strength' => 2, 'scaling_factor' => 0.1, 'armor' => 3]);

        $elvenBow = $createItem('Эльфийский композитный лук', ['type' => 'weapon', 'quality' => 1, 'base_price' => 100, 'min_damage' => 12, 'max_damage' => 18, 'agility' => 3, 'scaling_factor' => 0.15, 'required_class' => 'лучник']);
        $hunterHood = $createItem('Охотничий капюшон', ['type' => 'head', 'quality' => 1, 'base_price' => 80, 'agility' => 2, 'scaling_factor' => 0.1, 'required_class' => 'лучник', 'armor' => 1]);
        $studdedChest = $createItem('Клепаная куртка', ['type' => 'chest', 'quality' => 1, 'base_price' => 120, 'agility' => 3, 'scaling_factor' => 0.1, 'required_class' => 'лучник', 'armor' => 5]);
        $reinforcedLegs = $createItem('Укрепленные штаны', ['type' => 'legs', 'quality' => 1, 'base_price' => 90, 'agility' => 2, 'scaling_factor' => 0.1, 'armor' => 3]);
        $leatherBracers = $createItem('Кожаные наручи', ['type' => 'hands', 'quality' => 1, 'base_price' => 60, 'agility' => 2, 'scaling_factor' => 0.1, 'armor' => 2]);
        $lightBoots = $createItem('Легкие сапоги', ['type' => 'feet', 'quality' => 1, 'base_price' => 70, 'agility' => 2, 'scaling_factor' => 0.1, 'armor' => 2]);

        $darkStaff = $createItem('Посох темного культа', ['type' => 'weapon', 'quality' => 1, 'base_price' => 100, 'min_damage' => 12, 'max_damage' => 18, 'intelligence' => 3, 'scaling_factor' => 0.15, 'required_class' => 'маг']);
        $pointyHat = $createItem('Остроконечная шляпа', ['type' => 'head', 'quality' => 1, 'base_price' => 80, 'intelligence' => 3, 'scaling_factor' => 0.1, 'required_class' => 'маг']);
        $adeptRobe = $createItem('Мантия адепта', ['type' => 'chest', 'quality' => 1, 'base_price' => 120, 'intelligence' => 4, 'scaling_factor' => 0.1, 'required_class' => 'маг', 'armor' => 2]);
        $clothLegs = $createItem('Тканевые поножи', ['type' => 'legs', 'quality' => 1, 'base_price' => 90, 'intelligence' => 2, 'scaling_factor' => 0.1, 'armor' => 1]);
        $silkGloves = $createItem('Шелковые перчатки', ['type' => 'hands', 'quality' => 1, 'base_price' => 60, 'intelligence' => 2, 'scaling_factor' => 0.1, 'armor' => 1]);
        $magicSandals = $createItem('Магические сандалии', ['type' => 'feet', 'quality' => 1, 'base_price' => 70, 'intelligence' => 2, 'scaling_factor' => 0.1, 'armor' => 1]);

        $vitalityAmulet = $createItem('Амулет жизненной силы', ['type' => 'neck', 'quality' => 1, 'base_price' => 150, 'constitution' => 3, 'scaling_factor' => 0.15]);
        $focusRing = $createItem('Кольцо концентрации', ['type' => 'ring', 'quality' => 1, 'base_price' => 120, 'intelligence' => 2, 'luck' => 1, 'scaling_factor' => 0.15]);
        $wideBelt = $createItem('Широкий кожаный пояс', ['type' => 'belt', 'quality' => 1, 'base_price' => 90, 'constitution' => 2, 'scaling_factor' => 0.1, 'armor' => 1]);
        $luckTalisman = $createItem('Талисман удачи', ['type' => 'trinket', 'quality' => 1, 'base_price' => 140, 'luck' => 3, 'scaling_factor' => 0.15]);

        // Материалы
        $tail = $createItem('Хвост крысы', ['type' => 'material', 'quality' => 1, 'base_price' => 2, 'scaling_factor' => 0.05]);
        $goblinEar = $createItem('Ухо гоблина', ['type' => 'material', 'quality' => 1, 'base_price' => 5, 'scaling_factor' => 0.1]);
        $skeletonBone = $createItem('Кость скелета', ['type' => 'material', 'quality' => 1, 'base_price' => 8, 'scaling_factor' => 0.12]);
        $orcTusk = $createItem('Клык орка', ['type' => 'material', 'quality' => 2, 'base_price' => 15, 'scaling_factor' => 0.15]);


        // --- 2. ТАБЛИЦЫ ЛУТА ---

        $seedLootItems = function ($table, $items, $chancePerItem) use (&$lootItemIds) {
            foreach ($items as $item) {
                $li = LootItem::updateOrCreate(
                    ['loot_table_id' => $table->id, 'item_id' => $item->id],
                    ['chance' => $chancePerItem, 'min_quantity' => 1, 'max_quantity' => 1]
                );
                $lootItemIds->push($li->id);
            }
        };

        // Начальный лут
        $lootTable = LootTable::updateOrCreate(['name' => 'Начальный лут'], ['mode' => 'one', 'chance' => 15.0]);
        $lootTableIds->push($lootTable->id);
        $gearItems = [$sword, $bow, $staff, $helmetWarrior, $helmetArcher, $helmetMage, $chestWarrior, $chestArcher, $chestMage, $hands, $legs, $feet, $neck, $ring, $belt, $trinket];
        $seedLootItems($lootTable, $gearItems, 15.0);

        // Среднее снаряжение
        $midGearLootTable = LootTable::updateOrCreate(['name' => 'Среднее снаряжение'], ['mode' => 'one', 'chance' => 10.0]);
        $lootTableIds->push($midGearLootTable->id);
        $midGearItems = [$steelSword, $chainHelmet, $lamellarChest, $chainLegs, $ironHands, $heavyBoots, $elvenBow, $hunterHood, $studdedChest, $reinforcedLegs, $leatherBracers, $lightBoots, $darkStaff, $pointyHat, $adeptRobe, $clothLegs, $silkGloves, $magicSandals, $vitalityAmulet, $focusRing, $wideBelt, $luckTalisman];
        $seedLootItems($midGearLootTable, $midGearItems, 14.0);

        // Уникальный лут мобов
        $ratLT = LootTable::updateOrCreate(['name' => 'Лут крысы'], ['mode' => 'each']);
        $lootTableIds->push($ratLT->id);
        $li = LootItem::updateOrCreate(['loot_table_id' => $ratLT->id, 'item_id' => $tail->id], ['chance' => 60.0, 'min_quantity' => 1, 'max_quantity' => 2]);
        $lootItemIds->push($li->id);

        $goblinLT = LootTable::updateOrCreate(['name' => 'Лут гоблина'], ['mode' => 'each']);
        $lootTableIds->push($goblinLT->id);
        $li = LootItem::updateOrCreate(['loot_table_id' => $goblinLT->id, 'item_id' => $goblinEar->id], ['chance' => 40.0, 'min_quantity' => 1, 'max_quantity' => 1]);
        $lootItemIds->push($li->id);

        $skeletonLT = LootTable::updateOrCreate(['name' => 'Лут скелета'], ['mode' => 'each']);
        $lootTableIds->push($skeletonLT->id);
        $li = LootItem::updateOrCreate(['loot_table_id' => $skeletonLT->id, 'item_id' => $skeletonBone->id], ['chance' => 50.0, 'min_quantity' => 1, 'max_quantity' => 3]);
        $lootItemIds->push($li->id);

        $orcLT = LootTable::updateOrCreate(['name' => 'Лут орка'], ['mode' => 'each']);
        $lootTableIds->push($orcLT->id);
        $li = LootItem::updateOrCreate(['loot_table_id' => $orcLT->id, 'item_id' => $orcTusk->id], ['chance' => 30.0, 'min_quantity' => 1, 'max_quantity' => 1]);
        $lootItemIds->push($li->id);


        // --- 3. МОНСТРЫ ---

        $createEnemy = function($name, $data, $tables) use (&$enemyIds) {
            $enemy = Enemy::updateOrCreate(['name' => $name], $data);
            $enemy->lootTables()->sync($tables);
            $enemyIds->push($enemy->id);
        };

        $createEnemy('Крыса', ['level' => 1, 'strength' => 3, 'agility' => 5, 'constitution' => 4, 'intelligence' => 1, 'luck' => 2, 'min_damage' => 1, 'max_damage' => 2, 'base_experience' => 20, 'base_gold' => 5, 'scaling_factor' => 0.2, 'min_depth' => 1, 'max_depth' => 4, 'base_armor' => 0], [$lootTable->id, $ratLT->id]);
        $createEnemy('Слизень', ['level' => 1, 'strength' => 4, 'agility' => 3, 'constitution' => 10, 'intelligence' => 5, 'luck' => 1, 'min_damage' => 2, 'max_damage' => 3, 'base_experience' => 30, 'base_gold' => 8, 'scaling_factor' => 0.2, 'min_depth' => 3, 'max_depth' => 8, 'base_armor' => 0], [$lootTable->id]);
        $createEnemy('Волк', ['level' => 1, 'strength' => 7, 'agility' => 9, 'constitution' => 5, 'intelligence' => 2, 'luck' => 4, 'min_damage' => 4, 'max_damage' => 6, 'base_experience' => 45, 'base_gold' => 10, 'scaling_factor' => 0.2, 'min_depth' => 5, 'max_depth' => 12, 'base_armor' => 0], [$lootTable->id, $midGearLootTable->id]);
        $createEnemy('Гоблин', ['level' => 1, 'strength' => 9, 'agility' => 11, 'constitution' => 7, 'intelligence' => 4, 'luck' => 6, 'min_damage' => 4, 'max_damage' => 7, 'base_experience' => 75, 'base_gold' => 20, 'scaling_factor' => 0.18, 'min_depth' => 7, 'base_armor' => 1], [$lootTable->id, $goblinLT->id, $midGearLootTable->id]);
        $createEnemy('Скелет', ['level' => 1, 'strength' => 10, 'agility' => 8, 'constitution' => 9, 'intelligence' => 2, 'luck' => 2, 'min_damage' => 6, 'max_damage' => 10, 'base_experience' => 95, 'base_gold' => 25, 'scaling_factor' => 0.2, 'min_depth' => 10, 'base_armor' => 2], [$lootTable->id, $skeletonLT->id, $midGearLootTable->id]);
        $createEnemy('Орк', ['level' => 1, 'strength' => 13, 'agility' => 5, 'constitution' => 15, 'intelligence' => 3, 'luck' => 3, 'min_damage' => 8, 'max_damage' => 12, 'base_experience' => 150, 'base_gold' => 30, 'scaling_factor' => 0.2, 'min_depth' => 13, 'base_armor' => 0], [$lootTable->id, $orcLT->id, $midGearLootTable->id]);
        $createEnemy('Тролль', ['level' => 1, 'strength' => 15, 'agility' => 2, 'constitution' => 20, 'intelligence' => 1, 'luck' => 1, 'min_damage' => 12, 'max_damage' => 18, 'base_experience' => 220, 'base_gold' => 50, 'scaling_factor' => 0.2, 'min_depth' => 16, 'base_armor' => 1], [$lootTable->id, $midGearLootTable->id]);
        $createEnemy('Призрак', ['level' => 1, 'strength' => 3, 'agility' => 15, 'constitution' => 10, 'intelligence' => 20, 'luck' => 10, 'min_damage' => 10, 'max_damage' => 15, 'base_experience' => 250, 'base_gold' => 60, 'scaling_factor' => 0.2, 'min_depth' => 20, 'base_armor' => 0], [$lootTable->id, $midGearLootTable->id]);
        $createEnemy('Демон', ['level' => 1, 'strength' => 20, 'agility' => 15, 'constitution' => 25, 'intelligence' => 18, 'luck' => 10, 'min_damage' => 20, 'max_damage' => 35, 'base_experience' => 500, 'base_gold' => 150, 'scaling_factor' => 0.2, 'min_depth' => 25, 'base_armor' => 2], [$lootTable->id]);


        // --- ФИНАЛЬНАЯ ОЧИСТКА ---
        // Важно: удаляем сначала тех, кто ссылается (враги, лут-итемы),
        // потом тех, на кого ссылаются (таблицы, итемы).

        Enemy::whereNotIn('id', $enemyIds)->delete();
        LootItem::whereNotIn('id', $lootItemIds)->delete();
        LootTable::whereNotIn('id', $lootTableIds)->delete();
        Item::whereNotIn('id', $itemIds)->delete();
    }
}
