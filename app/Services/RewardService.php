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
        protected LootService $lootService
    ) {}

    /**
     * Наградить персонажа за победу над монстром
     */
    public function rewardCharacter(Character $character, Enemy $enemy): array
    {
        $enemyStats = $this->enemyService->calculateFinalStats($enemy);
        $luckBonus = $character->stats->rare_loot_bonus / 100;
        
        $xpReward = round($enemyStats['experience_reward'] * (1 + $luckBonus));
        $goldReward = round($enemyStats['gold_reward'] * (1 + $luckBonus));
        
        $loot = $this->lootService->generateLoot($enemy, $character);
        $dynamicGear = $this->lootService->rollDynamicGear($enemy, $character);

        DB::transaction(function () use ($character, $xpReward, $goldReward, $loot, $dynamicGear) {
            // 1. Опыт
            $this->characterService->addExperience($character, $xpReward);

            // 2. Золото
            $character->gold += $goldReward;
            $character->save();

            // 3. Табличный лут (хвосты и прочее)
            foreach ($loot as $itemId => $quantity) {
                $item = \App\Models\Item::find($itemId);
                
                if ($item && in_array($item->type, ['material', 'junk'])) {
                    $existing = CharacterItem::where('character_id', $character->id)
                        ->where('item_id', $itemId)
                        ->first();
                        
                    if ($existing) {
                        $existing->quantity += $quantity;
                        $existing->save();
                        continue;
                    }
                }

                if ($item && !in_array($item->type, ['material', 'junk'])) {
                    for ($i = 0; $i < $quantity; $i++) {
                        CharacterItem::create([
                            'character_id' => $character->id,
                            'item_id' => $itemId,
                            'ilevel' => 1,
                            'is_equipped' => false,
                            'quantity' => 1,
                        ]);
                    }
                } else {
                    CharacterItem::create([
                        'character_id' => $character->id,
                        'item_id' => $itemId,
                        'ilevel' => 1,
                        'is_equipped' => false,
                        'quantity' => $quantity,
                    ]);
                }
            }

            // 4. Динамическая экипировка
            if ($dynamicGear) {
                CharacterItem::create([
                    'character_id' => $character->id,
                    'item_id' => $dynamicGear['item_id'],
                    'ilevel' => $dynamicGear['ilevel'],
                    'is_equipped' => false,
                    'quantity' => 1,
                ]);
            }
        });

        return [
            'experience' => $xpReward,
            'gold' => $goldReward,
            'loot' => $loot,
            'dynamic_gear' => $dynamicGear,
        ];
    }
}
