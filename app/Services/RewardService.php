<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Enemy;
use App\Models\CharacterItem;
use Illuminate\Support\Facades\DB;

class RewardService
{
    public function __construct(
        protected CharacterService $characterService,
        protected EnemyService $enemyService,
        protected LootService $lootService,
        protected CurrencyService $currencyService
    ) {}

    /**
     * Наградить персонажа за победу над монстром
     */
    public function rewardCharacter(Character $character, Enemy $enemy, int $level = 1): array
    {
        $enemyStats = $this->enemyService->calculateFinalStats($enemy, $level);
        $luckBonus = ($character->stats->rare_loot_bonus ?? 0) / 100;
        
        $xpReward = round($enemyStats['experience_reward'] * (1 + $luckBonus));
        $goldReward = round($enemyStats['gold_reward'] * (1 + $luckBonus));
        
        // Генерируем весь лут (материалы + экипировка) одним вызовом
        $droppedItems = $this->lootService->generateLoot($enemy, $character, $level);

        DB::transaction(function () use ($character, $xpReward, $goldReward, $droppedItems) {
            // 1. Опыт
            $this->characterService->addExperience($character, $xpReward);

            // 2. Золото
            $this->currencyService->addGold($character, $goldReward);

            // 3. Прогресс подземелья (только на максимальной доступной глубине)
            if ($character->dungeon_depth >= $character->max_dungeon_depth) {
                $dynamic = $character->dynamicStats;
                $dynamic->increment('enemies_defeated_at_depth');
            }

            // 4. Сохраняем выпавшие предметы
            foreach ($droppedItems as $lootData) {
                $item = $lootData['item'];
                $quantity = $lootData['quantity'];
                $ilevel = $lootData['ilevel'];

                if ($item->isEquipment()) {
                    // Экипировка всегда создается как отдельные записи с уникальным iLvl
                    for ($i = 0; $i < $quantity; $i++) {
                        CharacterItem::create([
                            'character_id' => $character->id,
                            'item_id' => $item->id,
                            'ilevel' => $ilevel,
                            'quality' => $lootData['quality'] ?? $item->quality,
                            'is_equipped' => false,
                            'quantity' => 1,
                        ]);
                    }
                } else {
                    // Материалы и прочее стакаются
                    $existing = CharacterItem::where('character_id', $character->id)
                        ->where('item_id', $item->id)
                        ->first();
                        
                    if ($existing) {
                        $existing->quantity += $quantity;
                        $existing->save();
                    } else {
                        CharacterItem::create([
                            'character_id' => $character->id,
                            'item_id' => $item->id,
                            'ilevel' => 1,
                            'quality' => $lootData['quality'] ?? $item->quality,
                            'is_equipped' => false,
                            'quantity' => $quantity,
                        ]);
                    }
                }
            }
        });

        // Формируем результат для логов (разделяем на материалы и экипировку для читаемости)
        $lootResult = [];
        $gearResult = [];

        foreach ($droppedItems as $lootData) {
            $item = $lootData['item'];
            if ($item->isEquipment()) {
                $gearResult[] = [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'ilevel' => $lootData['ilevel'],
                    'quality' => $lootData['quality'] ?? $item->quality,
                ];
            } else {
                $lootResult[$item->id] = ($lootResult[$item->id] ?? 0) + $lootData['quantity'];
            }
        }

        return [
            'experience' => $xpReward,
            'gold' => $goldReward,
            'loot' => $lootResult,
            'gear' => $gearResult,
        ];
    }
}
