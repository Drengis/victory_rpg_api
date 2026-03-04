<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Quest;
use App\Models\CharacterQuest;
use App\Services\Core\BaseService;
use Illuminate\Support\Facades\DB;

class QuestService extends BaseService
{
    protected function getModel(): string
    {
        return Quest::class;
    }

    /**
     * Получить квесты персонажа (доступные и активные)
     */
    public function getQuestsForCharacter(Character $character): array
    {
        // 1. Сначала проверяем, какие новые квесты стали доступны
        $this->syncAvailableQuests($character);

        // 2. Возвращаем квесты со статусом available или active
        return $character->quests()
            ->whereIn('character_quests.status', ['available', 'active', 'ready'])
            ->get()
            ->toArray();
    }

    /**
     * Проверить условия и добавить новые доступные квесты
     */
    public function syncAvailableQuests(Character $character): void
    {
        $allQuests = Quest::all();
        $completedQuestIds = $character->quests()
            ->where('character_quests.status', 'completed')
            ->pluck('quests.id')
            ->toArray();

        foreach ($allQuests as $quest) {
            // Если квест уже есть у персонажа (в любом статусе), пропускаем
            if ($character->quests()->where('quest_id', $quest->id)->exists()) {
                continue;
            }

            $reqs = $quest->requirements;
            $canOpen = true;

            if ($reqs) {
                // Проверка уровня
                if (isset($reqs['level']) && $character->level < $reqs['level']) {
                    $canOpen = false;
                }

                // Проверка предыдущего квеста
                if ($canOpen && isset($reqs['quest_id']) && !in_array($reqs['quest_id'], $completedQuestIds)) {
                    $canOpen = false;
                }
            }

            if ($canOpen) {
                $character->quests()->attach($quest->id, [
                    'status' => 'available',
                    'current_value' => 0
                ]);
            }
        }
    }

    /**
     * Принять квест
     */
    public function acceptQuest(Character $character, int $questId): void
    {
        $pivot = CharacterQuest::where('character_id', $character->id)
            ->where('quest_id', $questId)
            ->where('status', 'available')
            ->first();

        if (!$pivot) {
            throw new \Exception("Квест недоступен для принятия.");
        }

        $pivot->update(['status' => 'active']);
        
        // Сразу проверяем прогресс (вдруг условия уже выполнены)
        $this->updateProgress($character, 'check_only', 0);
    }

    /**
     * Обновить прогресс квестов определенного типа
     */
    public function updateProgress(Character $character, string $type, int $amount): void
    {
        $activeQuests = $character->quests()
            ->where('character_quests.status', 'active')
            ->get();

        foreach ($activeQuests as $quest) {
            $updated = false;
            $pivot = $quest->pivot;

            if ($quest->type === $type) {
                $pivot->current_value += $amount;
                $updated = true;
            }
            
            // Специальные типы, которые не инкрементируются, а ставятся (уровень, золото)
            if ($quest->type === 'level' && $type === 'level_up') {
                $pivot->current_value = $character->level;
                $updated = true;
            }

            if ($quest->type === 'depth' && $type === 'reach_depth') {
                if ($character->dungeon_depth > $pivot->current_value) {
                    $pivot->current_value = $character->dungeon_depth;
                    $updated = true;
                }
            }

            if ($quest->type === 'gold' && $type === 'gold_check') {
                $pivot->current_value = $character->gold;
                $updated = true;
            }

            // Новый тип: Сбор предметов (loot)
            if ($quest->type === 'loot') {
                $itemId = $quest->requirements['item_id'] ?? null;
                if ($itemId) {
                    $pivot->current_value = $character->items()
                        ->where('item_id', $itemId)
                        ->sum('quantity');
                    $updated = true;
                }
            }

            // Если прогресс достиг цели, ставим статус ready
            if ($pivot->current_value >= $quest->target_value) {
                $pivot->status = 'ready';
                $updated = true;
            }

            if ($updated) {
                $pivot->save();
            }
        }
    }

    /**
     * Забрать награду
     */
    public function claimReward(Character $character, int $questId): array
    {
        return DB::transaction(function () use ($character, $questId) {
            $quest = $character->quests()
                ->where('quests.id', $questId)
                ->where('character_quests.status', 'ready')
                ->first();

            if (!$quest) {
                throw new \Exception("Награда недоступна.");
            }

            $rewards = $quest->rewards;
            $result = [];

            // Если это квест на сбор предметов (loot), изымаем их
            if ($quest->type === 'loot') {
                $itemId = $quest->requirements['item_id'] ?? null;
                $needed = $quest->target_value;
                if ($itemId) {
                    $items = \App\Models\CharacterItem::where('character_id', $character->id)
                        ->where('item_id', $itemId)
                        ->orderBy('quantity', 'asc')
                        ->get();
                    
                    $remaining = $needed;
                    foreach ($items as $charItem) {
                        if ($remaining <= 0) break;
                        $take = min($charItem->quantity, $remaining);
                        if ($charItem->quantity > $take) {
                            $charItem->quantity -= $take;
                            $charItem->save();
                        } else {
                            $charItem->delete();
                        }
                        $remaining -= $take;
                    }
                }
            }

            // 1. Золото
            if (isset($rewards['gold'])) {
                $character->gold += $rewards['gold'];
                $result['gold'] = $rewards['gold'];
            }

            // 2. Опыт
            if (isset($rewards['xp'])) {
                app(CharacterService::class)->addExperience($character, $rewards['xp']);
                $result['xp'] = $rewards['xp'];
            }

            // 3. Очки характеристик
            if (isset($rewards['stat_points'])) {
                $character->stat_points += $rewards['stat_points'];
                $result['stat_points'] = $rewards['stat_points'];
            }

            // 4. Предметы (по ID)
            if (isset($rewards['items']) && is_array($rewards['items'])) {
                foreach ($rewards['items'] as $itemId) {
                    $item = \App\Models\Item::find($itemId);
                    if ($item) {
                        app(CharacterService::class)->addItemToCharacter($character, $item, 1);
                    }
                }
                $result['items_count'] = count($rewards['items']);
            }

            // 5. Случайное снаряжение (random_gear)
            if (isset($rewards['random_gear'])) {
                $quality = $rewards['random_gear']['quality'] ?? 1;
                $ilevel = $rewards['random_gear']['ilevel'] ?? $character->level;
                
                $query = \App\Models\Item::where('quality', $quality);
                
                // Фильтр по классу
                $charClass = mb_strtolower($character->class);
                $query->where(function($q) use ($charClass) {
                    $q->whereNull('required_class')
                      ->orWhere('required_class', $charClass);
                });
                
                // Только снаряжение
                $query->whereIn('type', ['weapon', 'head', 'chest', 'hands', 'legs', 'feet', 'neck', 'ring', 'belt', 'trinket']);
                
                $randomItem = $query->inRandomOrder()->first();
                
                if ($randomItem) {
                    app(CharacterService::class)->addItemToCharacter($character, $randomItem, 1, $ilevel);
                    $result['random_item'] = $randomItem->name;
                }
            }

            // 6. Случайное среднее снаряжение (random_mid_gear)
            if (isset($rewards['random_mid_gear'])) {
                // Среднее снаряжение имеет ID с 17 по 38 в сидере
                $query = \App\Models\Item::whereBetween('id', [17, 38]);
                
                // Фильтр по классу (только для оружия/брони, где есть класс)
                $charClass = mb_strtolower($character->class);
                $query->where(function($q) use ($charClass) {
                    $q->whereNull('required_class')
                      ->orWhere('required_class', $charClass);
                });
                
                $randomItem = $query->inRandomOrder()->first();
                
                if ($randomItem) {
                    // Выдаем предмет с илевлом текущего уровня персонажа
                    app(CharacterService::class)->addItemToCharacter($character, $randomItem, 1, $character->level);
                    $result['random_mid_item'] = $randomItem->name;
                }
            }

            $character->save();

            // Обновляем статус квеста
            $character->quests()->updateExistingPivot($questId, ['status' => 'completed']);

            // После завершения проверяем, не открылись ли новые квесты
            $this->syncAvailableQuests($character);

            return $result;
        });
    }
}
