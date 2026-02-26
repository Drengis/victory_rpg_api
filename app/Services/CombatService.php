<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Combat;
use App\Models\CombatParticipant;
use App\Models\Enemy;
use Illuminate\Support\Facades\DB;

class CombatService
{
    protected EnemyService $enemyService;
    protected AbilityService $abilityService;
    protected RewardService $rewardService;
    protected CharacterService $characterService;

    public function __construct(
        EnemyService $enemyService, 
        AbilityService $abilityService, 
        RewardService $rewardService,
        CharacterService $characterService
    ) {
        $this->enemyService = $enemyService;
        $this->abilityService = $abilityService;
        $this->rewardService = $rewardService;
        $this->characterService = $characterService;
    }

    /**
     * Начало боя
     */
    public function startCombat(Character $character, array $enemyIds): Combat
    {
        return DB::transaction(function () use ($character, $enemyIds) {
            // 1. Создаем запись боя
            $firstTurn = rand(1, 100) <= 50 ? 'player' : 'enemies';
            
            $combat = Combat::create([
                'character_id' => $character->id,
                'status' => 'active',
                'current_turn' => $firstTurn,
                'turn_number' => 1,
            ]);

            // 2. Добавляем участников (врагов)
            foreach ($enemyIds as $index => $enemyId) {
                $enemy = Enemy::find($enemyId);
                $scaledEnemy = $this->enemyService->calculateFinalStats($enemy);

                CombatParticipant::create([
                    'combat_id' => $combat->id,
                    'enemy_id' => $enemyId,
                    'current_hp' => $scaledEnemy['max_hp'],
                    'current_mp' => $scaledEnemy['max_mp'],
                    'level' => $enemy->level,
                    'position' => $index + 1,
                ]);
            }

            // 3. Помечаем персонажа как "в бою"
            $character->dynamicStats->update(['is_in_combat' => true]);

            // 4. Если враги ходят первыми — обрабатываем их ход сразу
            if ($firstTurn === 'enemies') {
                $logs = $this->processEnemiesTurn($combat);
                $character->dynamicStats->update([
                    'last_combat_log' => implode("\n", $logs)
                ]);
            }

            return $combat;
        });
    }

    /**
     * Действие: Атака
     */
    public function performAttack(Combat $combat, int $participantId): array
    {
        if ($combat->current_turn !== 'player') {
            throw new \Exception("Сейчас не ваш ход.");
        }

        return DB::transaction(function () use ($combat, $participantId) {
            $character = $combat->character;
            $stats = $character->stats;
            $participant = CombatParticipant::findOrFail($participantId);
            $enemy = $participant->enemy;
            $enemyStats = $this->enemyService->calculateFinalStats($enemy);

            // 1. Проверка на попадание (Меткость атакующего vs Уклонение защитника)
            // Базовый шанс попадания 85%. Прибавляем меткость игрока, вычитаем уклонение врага.
            $hitChance = 85 + $stats->accuracy - ($enemyStats['evasion'] ?? 0);
            $hitChance = max(5, min(95, $hitChance)); // Шанс попадания от 5% до 95%
            
            $logs = [];
            if (rand(1, 100) > $hitChance) {
                $logs[] = "Вы промахнулись по противнику {$enemy->name}!";
                $enemyLogs = $this->endPlayerTurn($combat);
                $logs = array_merge($logs, $enemyLogs);

                return [
                    'action' => 'attack',
                    'damage' => 0,
                    'logs' => $logs,
                    'status' => $combat->status,
                ];
            }

            // 2. Расчет базового урона
            $damage = rand($stats->min_damage, $stats->max_damage);
            $isCrit = rand(1, 1000) <= ($stats->crit_chance * 10);
            
            if ($isCrit) {
                $damage *= 2;
            }

            $finalDamage = max(1, $damage);

            $participant->current_hp -= $finalDamage;
            $participant->save();

            $log = "Вы нанесли {$finalDamage} урона противнику {$enemy->name}.";
            if ($isCrit) {
                $log = "✨ КРИТ! " . $log;
            }
            $logs[] = $log;
            
            if ($participant->current_hp <= 0) {
                $rewards = $this->rewardService->rewardCharacter($character, $enemy);
                $participant->delete();
                
                // Накапливаем награды в объекте боя
                $combat->gold_reward += $rewards['gold'];
                $combat->experience_reward += $rewards['experience'];
                
                $currentLoot = $combat->loot_reward ?? [];
                foreach ($rewards['loot'] as $itemId => $qty) {
                    $item = \App\Models\Item::find($itemId);
                    $itemName = $item ? $item->name : "Неизвестный предмет";
                    
                    if (isset($currentLoot[$itemName])) {
                        $currentLoot[$itemName] += $qty;
                    } else {
                        $currentLoot[$itemName] = $qty;
                    }
                }
                
                if (isset($rewards['dynamic_gear'])) {
                    $item = \App\Models\Item::find($rewards['dynamic_gear']['item_id']);
                    $itemName = ($item ? $item->name : "Снаряжение") . " (iLvl " . $rewards['dynamic_gear']['ilevel'] . ")";
                    $currentLoot[$itemName] = 1;
                }
                
                $combat->loot_reward = $currentLoot;
                $combat->save();

                $logs[] = "☠️ Противник {$enemy->name} повержен! Получено опыта: {$rewards['experience']}, золота: {$rewards['gold']}.";
            }

            if ($combat->participants()->count() === 0) {
                $this->finishCombat($combat, 'won');
            } else {
                $enemyLogs = $this->endPlayerTurn($combat);
                $logs = array_merge($logs, $enemyLogs);
            }

            return [
                'action' => 'attack',
                'damage' => $finalDamage,
                'is_crit' => $isCrit,
                'logs' => $logs,
                'status' => $combat->status,
            ];
        });
    }

    /**
     * Действие: Защита
     */
    public function performDefense(Combat $combat): array
    {
        if ($combat->current_turn !== 'player') {
            throw new \Exception("Сейчас не ваш ход.");
        }

        $abilityLog = DB::transaction(function () use ($combat) {
            $character = $combat->character;
            $totalStats = (new CharacterService())->calculateFinalStats($character);
            $class = mb_strtolower($character->class);

            // Получаем способность для класса
            $ability = $this->abilityService->getAbilityForClass($class, 'defense');
            
            if (!$ability) {
                throw new \Exception("Защитная способность для класса {$class} не найдена.");
            }

            // Проверяем доступность
            $canUse = $this->abilityService->canUseAbility($ability, $combat, $character);
            if (!$canUse['can_use']) {
                throw new \Exception($canUse['reason']);
            }

            // Используем способность
            $result = $this->abilityService->useAbility($ability, $combat, $character, $totalStats);
            
            $log = "🔮 {$result['ability_name']}: эффект +{$result['effect_value']}";
            if ($result['mp_spent'] > 0) {
                $log .= " (мана: -{$result['mp_spent']}, осталось: {$result['mp_remaining']})";
            }
            
            return $log;
        });

        // Ход врагов вызываем ПОСЛЕ завершения транзакции
        $enemyLogs = $this->endPlayerTurn($combat);
        $logs = array_merge([$abilityLog], $enemyLogs);

        return [
            'action' => 'defense',
            'logs' => $logs,
            'status' => $combat->status,
        ];
    }

    /**
     * Действие: Побег
     */
    public function performFlee(Combat $combat): array
    {
        if ($combat->current_turn !== 'player') {
            throw new \Exception("Сейчас не ваш ход.");
        }

        $character = $combat->character;
        $charStats = $character->stats;
        
        // 1. Находим самого "быстрого" врага
        $maxEnemyAgility = 0;
        foreach ($combat->participants as $participant) {
            $enemyStats = $this->enemyService->calculateFinalStats($participant->enemy);
            $maxEnemyAgility = max($maxEnemyAgility, $enemyStats['agility'] ?? 0);
        }

        // 2. Расчет шанса: Базовый 50% + разница в ловкости
        $fleeChance = 50 + ($charStats->agility - $maxEnemyAgility) * 2;
        $fleeChance = max(10, min(90, $fleeChance)); // Шанс от 10% до 90%

        $success = rand(1, 100) <= $fleeChance;

        $logs = ["Вы попытались сбежать (шанс: {$fleeChance}%)..."];
        if ($success) {
            $this->finishCombat($combat, 'fled');
            $logs[] = 'Вы успешно сбежали с поля боя!';
            return [
                'success' => true,
                'logs' => $logs,
                'status' => 'fled'
            ];
        }

        $logs[] = "Побег не удался! Враги окружают вас.";
        $enemyLogs = $this->endPlayerTurn($combat);
        $logs = array_merge($logs, $enemyLogs);
        
        return [
            'success' => false,
            'logs' => $logs,
            'status' => 'active'
        ];
    }

    /**
     * Завершение хода игрока
     */
    public function endPlayerTurn(Combat $combat): array
    {
        // Начисляем регенерацию за раунд
        $this->characterService->applyCombatRoundRegen($combat->character);
        
        $combat->update(['current_turn' => 'enemies']);
        return $this->processEnemiesTurn($combat);
    }

    /**
     * Логика хода врагов
     */
    public function processEnemiesTurn(Combat $combat): array
    {
        $character = $combat->character;
        $dynamic = $character->dynamicStats()->first();
        $charStats = $character->stats;
        $logs = [];

        foreach ($combat->participants as $participant) {
            $enemy = $participant->enemy;
            $enemyStats = $this->enemyService->calculateFinalStats($enemy);
            
            // 1. Попадание (Меткость врага vs Уклонение игрока)
            $enemyAccuracy = $enemyStats['accuracy'] ?? 0;
            $playerEvasion = $charStats->evasion + $dynamic->temp_evasion;
            
            $hitChance = 85 + $enemyAccuracy - $playerEvasion;
            $hitChance = max(5, min(95, $hitChance));

            if (rand(1, 100) > $hitChance) {
                $logs[] = "💨 Враг {$enemy->name} промахнулся (уклонение)!";
                continue;
            }

            // 2. Урон + Криты для мобов
            $damage = rand($enemyStats['min_damage'], $enemyStats['max_damage']);
            
            // Крит моба на основе его удачи
            $critRoll = rand(1, 1000);
            $isCrit = $critRoll <= (($enemyStats['luck'] * 0.3) * 10);
            if ($isCrit) {
                $damage *= 1.5; // Криты мобов чуть слабее (1.5x вместо 2x)
            }

            $totalArmor = $charStats->armor + $dynamic->temp_armor;
            $finalDamage = max(1, round($damage - $totalArmor));

            // 3. Барьер
            if ($dynamic->barrier_hp > 0 && $finalDamage > 0) {
                $absorbed = min($dynamic->barrier_hp, $finalDamage);
                $dynamic->barrier_hp -= $absorbed;
                $finalDamage -= $absorbed;
                $logs[] = "🛡️ Магический барьер поглотил {$absorbed} урона от {$enemy->name}.";
            }

            if ($finalDamage > 0) {
                $dynamic->current_hp -= $finalDamage;
                $msg = "💥 Враг {$enemy->name} нанес вам {$finalDamage} урона.";
                if ($isCrit) $msg = "⚡ КРИТ! " . $msg;
                $logs[] = $msg;
            } else {
                $logs[] = "🛡️ Ваша броня полностью заблокировала атаку {$enemy->name}!";
            }
        }

        // Сброс временных бонусов (барьер НЕ сбрасывается, он расходуется постепенно)
        $dynamic->temp_armor = 0;
        $dynamic->temp_evasion = 0;
        $dynamic->last_combat_log = implode("\n", $logs);
        $dynamic->save();

        if ($dynamic->current_hp <= 0) {
            $this->finishCombat($combat, 'lost');
            $logs[] = "💀 Вы пали в бою...";
        } else {
            $combat->increment('turn_number');
            $combat->update(['current_turn' => 'player']);
        }

        return $logs;
    }

    /**
     * Завершение боя
     */
    protected function finishCombat(Combat $combat, string $status): void
    {
        $combat->update(['status' => $status]);
        $combat->character->dynamicStats()->update([
            'is_in_combat' => false,
            'barrier_hp' => 0,
            'temp_armor' => 0,
            'temp_evasion' => 0
        ]);
    }
}
