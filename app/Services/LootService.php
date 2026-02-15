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
     * @return array Массив [item_id => quantity]
     */
    public function generateLoot(Enemy $enemy): array
    {
        if (!$enemy->loot_table_id) {
            return [];
        }

        $items = LootItem::where('loot_table_id', $enemy->loot_table_id)->get();
        $rolledLoot = [];

        foreach ($items as $lootItem) {
            $roll = rand(0, 10000) / 100; // 0.00 - 100.00%

            if ($roll <= $lootItem->chance) {
                $quantity = rand($lootItem->min_quantity, $lootItem->max_quantity);
                
                if (isset($rolledLoot[$lootItem->item_id])) {
                    $rolledLoot[$lootItem->item_id] += $quantity;
                } else {
                    $rolledLoot[$lootItem->item_id] = $quantity;
                }
            }
        }

        return $rolledLoot;
    }

    /**
     * Сгенерировать случайную экипировку на основе уровня монстра
     */
    public function rollDynamicGear(Enemy $enemy): ?array
    {
        // Базовый шанс на выпадение любой шмотки 5%
        if (rand(1, 100) > 5) {
            return null;
        }

        // 1. Определяем редкость (Quality) на основе уровня врага
        $quality = 1; // Common
        $roll = rand(1, 100);

        if ($enemy->level >= 25) {
            if ($roll <= 5) $quality = 3; // 5% Blue
            elseif ($roll <= 30) $quality = 2; // 25% Green
        } elseif ($enemy->level >= 10) {
            if ($roll <= 20) $quality = 2; // 20% Green
        }

        // 2. Определяем слот (Type)
        $slotRoll = rand(1, 100);
        $type = 'chest';
        
        if ($slotRoll <= 15) $type = 'weapon';           // 15%
        elseif ($slotRoll <= 25) $type = 'head';         // 10%
        elseif ($slotRoll <= 40) $type = 'chest';        // 15%
        elseif ($slotRoll <= 50) $type = 'hands';        // 10%
        elseif ($slotRoll <= 60) $type = 'legs';         // 10%
        elseif ($slotRoll <= 70) $type = 'feet';         // 10%
        elseif ($slotRoll <= 80) $type = 'belt';         // 10%
        elseif ($slotRoll <= 88) $type = 'neck';         // 8%
        elseif ($slotRoll <= 96) $type = 'ring';         // 8%
        else $type = 'trinket';                          // 4%

        // 3. Ищем случайный шаблон предмета
        $itemTemplate = \App\Models\Item::where('type', $type)
            ->where('quality', $quality)
            ->inRandomOrder()
            ->first();

        if (!$itemTemplate) {
            // Если не нашли нужного качества, пробуем Common
            $itemTemplate = \App\Models\Item::where('type', $type)
                ->where('quality', 1)
                ->inRandomOrder()
                ->first();
        }

        if (!$itemTemplate) return null;

        // 4. Генерируем iLvl (enemy level +/- 2)
        $ilevel = max(1, $enemy->level + rand(-2, 2));

        return [
            'item_id' => $itemTemplate->id,
            'ilevel' => $ilevel,
        ];
    }
}
