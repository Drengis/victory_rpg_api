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
    public function generateLoot(Enemy $enemy, ?\App\Models\Character $character = null): array
    {
        $lootTables = $enemy->lootTables;
        if ($lootTables->isEmpty()) {
            return [];
        }

        $luckBonus = $character ? ($character->stats->rare_loot_bonus / 100) : 0;
        $rolledLoot = [];

        foreach ($lootTables as $table) {
            $items = LootItem::where('loot_table_id', $table->id)->get();

            if ($table->mode === 'one') {
                // Режим "один предмет": chance используется как вес
                $totalWeight = $items->sum('chance');
                $roll = rand(0, (int)($totalWeight * 100)) / 100;

                $cumulative = 0;
                foreach ($items as $lootItem) {
                    $effectiveWeight = $lootItem->chance * (1 + $luckBonus);
                    $cumulative += $effectiveWeight;

                    if ($roll <= $cumulative) {
                        $quantity = rand($lootItem->min_quantity, $lootItem->max_quantity);
                        if (isset($rolledLoot[$lootItem->item_id])) {
                            $rolledLoot[$lootItem->item_id] += $quantity;
                        } else {
                            $rolledLoot[$lootItem->item_id] = $quantity;
                        }
                        break;
                    }
                }
            } else {
                // Режим "each" (по умолчанию): каждый предмет ролится независимо
                foreach ($items as $lootItem) {
                    $roll = rand(0, 10000) / 100;
                    $effectiveChance = $lootItem->chance * (1 + $luckBonus);

                    if ($roll <= $effectiveChance) {
                        $quantity = rand($lootItem->min_quantity, $lootItem->max_quantity);
                        if (isset($rolledLoot[$lootItem->item_id])) {
                            $rolledLoot[$lootItem->item_id] += $quantity;
                        } else {
                            $rolledLoot[$lootItem->item_id] = $quantity;
                        }
                    }
                }
            }
        }

        return $rolledLoot;
    }

    /**
     * Сгенерировать случайную экипировку на основе уровня монстра
     */
    public function rollDynamicGear(Enemy $enemy, ?\App\Models\Character $character = null): ?array
    {
        $luckBonus = 0;
        if ($character && $character->stats) {
            $luckBonus = $character->stats->rare_loot_bonus / 100;
        }
        
        // Базовый шанс на выпадение любой шмотки 5%, масштабируется удачей
        $baseChance = 5 * (1 + $luckBonus);
        if (rand(1, 100) > $baseChance) {
            return null;
        }

        // 1. Определяем редкость (Quality) на основе уровня врага и удачи
        $quality = 1; // Common
        $roll = rand(1, 100);
        
        // Бонус удачи увеличивает шансы на редкое качество
        $blueChance = 5 * (1 + $luckBonus);
        $greenChance25 = 30 * (1 + $luckBonus);
        $greenChance20 = 20 * (1 + $luckBonus);

        if ($enemy->level >= 25) {
            if ($roll <= $blueChance) $quality = 3; // Blue
            elseif ($roll <= $greenChance25) $quality = 2; // Green
        } elseif ($enemy->level >= 10) {
            if ($roll <= $greenChance20) $quality = 2; // Green
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
