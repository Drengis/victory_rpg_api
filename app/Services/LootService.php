<?php

namespace App\Services;

use App\Models\Enemy;
use App\Models\LootItem;
use App\Services\Core\BaseService;

class LootService extends BaseService
{
    protected function getModel(): string
    {
        return LootItem::class;
    }

    /**
     * Сгенерировать лут для убитого монстра
     * @return array Список выпавших предметов [['item' => Item, 'quantity' => int, 'ilevel' => int]]
     */
    public function generateLoot(Enemy $enemy, ?\App\Models\Character $character = null, int $level = 1): array
    {
        $lootTables = $enemy->lootTables;
        if ($lootTables->isEmpty()) {
            return [];
        }

        $luckBonus = $character ? ($character->stats->rare_loot_bonus / 100) : 0;
        $allDroppedItems = [];

        foreach ($lootTables as $table) {
            // Проверка общего шанса таблицы
            $tableChance = $table->chance ?? 100.0;
            if (rand(0, 10000) / 100 > $tableChance) {
                continue;
            }

            $items = LootItem::where('loot_table_id', $table->id)->with('item')->get();

            if ($table->mode === 'one') {
                // Режим "один предмет": chance используется как вес
                $totalWeight = $items->sum('chance');
                if ($totalWeight <= 0) continue;

                $roll = rand(0, (int)($totalWeight * 100)) / 100;
                $cumulative = 0;
                foreach ($items as $lootItem) {
                    $cumulative += $lootItem->chance;
                    if ($roll <= $cumulative) {
                        $allDroppedItems[] = $this->processLootItem($lootItem, $level, $character);
                        break;
                    }
                }
            } else {
                // Режим "each": каждый предмет ролится независимо
                foreach ($items as $lootItem) {
                    $roll = rand(0, 10000) / 100;
                    $effectiveChance = $lootItem->chance * (1 + $luckBonus);

                    if ($roll <= $effectiveChance) {
                        $allDroppedItems[] = $this->processLootItem($lootItem, $level, $character);
                    }
                }
            }
        }

        return $allDroppedItems;
    }

    /**
     * Обработка конкретного выпавшего предмета (уровень, количество, редкость)
     */
    protected function processLootItem(LootItem $lootItem, int $level, ?\App\Models\Character $character = null): array
    {
        $item = $lootItem->item;
        $quantity = rand($lootItem->min_quantity, $lootItem->max_quantity);
        $ilevel = 1;
        $quality = $item->quality;

        if ($item->isEquipment()) {
            // Генерируем iLvl (actual level +/- 2)
            $ilevel = max(1, $level + rand(-2, 2));

            // Логика динамической редкости
            $depth = $character ? $character->dungeon_depth : 1;
            $luckBonus = $character ? ($character->stats->rare_loot_bonus / 100) : 0;
            
            $newQuality = $quality;
            
            // Шанс на Uncommon (Зеленый) с 5 этажа
            if ($newQuality < \App\Models\Item::QUALITY_UNCOMMON && $depth >= 5) {
                $chance = min(50.0, ($depth / 5) * 10.0) * (1 + $luckBonus);
                if (rand(0, 10000) / 100 <= $chance) {
                    $newQuality = \App\Models\Item::QUALITY_UNCOMMON;
                }
            }

            // Шанс на Rare (Синий) с 12 этажа
            if ($newQuality < \App\Models\Item::QUALITY_RARE && $depth >= 12) {
                $chance = min(25.0, (($depth - 7) / 5) * 10.0) * (1 + $luckBonus);
                if (rand(0, 10000) / 100 <= $chance) {
                    $newQuality = \App\Models\Item::QUALITY_RARE;
                }
            }
            
            $quality = $newQuality;
        }

        return [
            'item' => $item,
            'quantity' => $quantity,
            'ilevel' => $ilevel,
            'quality' => $quality,
        ];
    }
}
