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
    protected QuestService $questService;

    public function __construct(
        EnemyService $enemyService,
        AbilityService $abilityService,
        RewardService $rewardService,
        CharacterService $characterService,
        QuestService $questService
    ) {
        $this->enemyService = $enemyService;
        $this->abilityService = $abilityService;
        $this->rewardService = $rewardService;
        $this->characterService = $characterService;
        $this->questService = $questService;
    }

    /**
     * Начало боя
     */
    public function startCombat(Character $character, array $enemyIds, array $enemyLevels = []): Combat
    {
        return DB::transaction(function () use ($character, $enemyIds, $enemyLevels) {
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
                $targetLevel = $enemyLevels[$index] ?? $enemy->level;
                $scaledEnemy = $this->enemyService->calculateFinalStats($enemy, $targetLevel);

                CombatParticipant::create([
                    'combat_id' => $combat->id,
                    'enemy_id' => $enemyId,
                    'current_hp' => $scaledEnemy['max_hp'],
                    'max_hp' => $scaledEnemy['max_hp'],
                    'current_mp' => $scaledEnemy['max_mp'],
                    'max_mp' => $scaledEnemy['max_mp'],
                    'level' => $targetLevel,
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
            $enemyStats = $this->enemyService->calculateFinalStats($enemy, $participant->level);

            \Illuminate\Support\Facades\Log::debug('PLAYER_STATS_DEBUG', [
                'character' => $character->name,
                'class' => $character->class,
                'min_damage' => $stats->min_damage,
                'max_damage' => $stats->max_damage,
                'physical_damage_bonus' => $stats->physical_damage_bonus,
                'magical_damage_bonus' => $stats->magical_damage_bonus,
                'crit_chance' => $stats->crit_chance,
                'armor' => $stats->armor,
                'accuracy' => $stats->accuracy,
            ]);
            \Illuminate\Support\Facades\Log::debug('ENEMY_STATS_DEBUG', [
                'enemy' => $enemy->name,
                'min_damage' => $enemyStats['min_damage'],
                'max_damage' => $enemyStats['max_damage'],
                'armor' => $enemyStats['armor'],
                'evasion' => $enemyStats['evasion'],
                'luck' => $enemyStats['luck'],
                'hp_regen' => $enemyStats['hp_regen'],
            ]);

            // 1. Проверка на попадание (Меткость атакующего vs Уклонение защитника)
            // Базовый шанс попадания 75%. Прибавляем меткость игрока, вычитаем уклонение врага.
            $rawHitChance = 75 + $stats->accuracy - ($enemyStats['evasion'] ?? 0);
            $excessHitChance = max(0, $rawHitChance - 95);
            $hitChance = min(95, $rawHitChance);
            $hitChance = max(5, $hitChance); // Шанс попадания от 5% до 95%

            // Бонус к криту от избыточного шанса попадания
            $critBonus = $excessHitChance / 2;

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
            $baseDamage = rand($stats->min_damage, $stats->max_damage);
            $effectiveCritChance = $stats->crit_chance + $critBonus;
            $isCrit = rand(1, 1000) <= ($effectiveCritChance * 10);
            $critMultiplier = $isCrit ? 2 : 1;

            if ($isCrit) {
                $baseDamage *= 2;
            }

            // Применяем броню врага
            $enemyArmor = $enemyStats['armor'] ?? 0;
            $finalDamage = max(1, round($baseDamage - $enemyArmor));

            \Illuminate\Support\Facades\Log::debug('ATTACK_DEBUG', [
                'character' => $character->name,
                'enemy' => $enemy->name,
                'base_damage_range' => $stats->min_damage . '-' . $stats->max_damage,
                'rolled_damage' => $baseDamage / $critMultiplier,
                'raw_hit_chance' => $rawHitChance,
                'excess_hit_chance' => $excessHitChance,
                'crit_bonus' => $critBonus,
                'base_crit_chance' => $stats->crit_chance,
                'effective_crit_chance' => $effectiveCritChance,
                'crit' => $isCrit,
                'crit_multiplier' => $critMultiplier,
                'enemy_armor' => $enemyArmor,
                'final_damage' => $finalDamage,
            ]);

            $participant->current_hp -= $finalDamage;
            $participant->save();

            $log = "Вы нанесли {$finalDamage} урона противнику {$enemy->name}. (Шанс попадания: {$hitChance}%, Бонус крита: +{$critBonus}%, Крит: " . ($effectiveCritChance * 10 / 10) . "%)";
            if ($isCrit) {
                $log = "✨ КРИТ! " . $log;
            }
            $logs[] = $log;

            if ($participant->current_hp <= 0) {
                $rewards = $this->rewardService->rewardCharacter($character, $enemy, $participant->level);
                $participant->delete();

                // Накапливаем награды в объекте боя
                $combat->gold_reward += $rewards['gold'];
                $combat->experience_reward += $rewards['experience'];

                $currentLoot = $combat->loot_reward ?? [];
                // Обработка материалов
                foreach ($rewards['loot'] as $itemId => $qty) {
                    $item = \App\Models\Item::find($itemId);
                    $itemName = $item ? $item->name : "Неизвестный предмет";

                    if (isset($currentLoot[$itemName])) {
                        $currentLoot[$itemName] += $qty;
                    } else {
                        $currentLoot[$itemName] = $qty;
                    }
                }

                // Обработка снаряжения
                foreach ($rewards['gear'] as $gear) {
                    $itemName = $gear['name'] . " (iLvl " . $gear['ilevel'] . ")";
                    if (isset($currentLoot[$itemName])) {
                        $currentLoot[$itemName] += 1;
                    } else {
                        $currentLoot[$itemName] = 1;
                    }
                }

                $combat->loot_reward = $currentLoot;
                $combat->save();

                $logs[] = "☠️ Противник {$enemy->name} повержен!";

                // Обновляем прогресс квестов
                $this->questService->updateProgress($character, 'kills', 1);
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
     * Действие: Использование способности
     */
    public function performAbility(Combat $combat, int $abilityId, ?int $targetId = null): array
    {
        if ($combat->current_turn !== 'player') {
            throw new \Exception("Сейчас не ваш ход.");
        }

        $result = DB::transaction(function () use ($combat, $abilityId, $targetId) {
            $character = $combat->character;
            $ability = \App\Models\ClassAbility::find($abilityId);

            if (!$ability) {
                throw new \Exception("Способность не найдена.");
            }

            // Проверяем, принадлежит ли способность классу
            if (mb_strtolower($character->class) !== mb_strtolower($ability->class)) {
                throw new \Exception("Эта способность недоступна вашему классу.");
            }

            // Проверяем доступность (уровень, мана, использование)
            $canUse = $this->abilityService->canUseAbility($ability, $combat, $character);
            if (!$canUse['can_use']) {
                throw new \Exception($canUse['reason']);
            }

            $totalStats = $this->characterService->calculateFinalStats($character);

            // Используем способность
            $useResult = $this->abilityService->useAbility($ability, $combat, $character, $totalStats, $targetId);

            $logs = [];
            $emoji = $ability->ability_type === 'defense' ? '🔮' : '💥';
            $log = "{$emoji} {$useResult['ability_name']}: ";

            if ($ability->effect_type === 'deal_damage') {
                $target = $combat->participants()->find($targetId);
                $enemyName = $target ? $target->enemy->name : "врага";
                $log .= "нанесено {$useResult['damage_dealt']} урона {$enemyName}";

                // Проверяем смерть врага
                if ($target && $target->current_hp <= 0) {
                    $enemy = $target->enemy;
                    $rewards = $this->rewardService->rewardCharacter($character, $enemy, $target->level);
                    $target->delete();

                    // Накапливаем награды в объекте боя
                    $combat->gold_reward += $rewards['gold'];
                    $combat->experience_reward += $rewards['experience'];

                    $currentLoot = $combat->loot_reward ?? [];
                    // Обработка материалов
                    foreach ($rewards['loot'] as $itemId => $qty) {
                        $item = \App\Models\Item::find($itemId);
                        $itemName = $item ? $item->name : "Неизвестный предмет";

                        if (isset($currentLoot[$itemName])) {
                            $currentLoot[$itemName] += $qty;
                        } else {
                            $currentLoot[$itemName] = $qty;
                        }
                    }

                    // Обработка снаряжения
                    foreach ($rewards['gear'] as $gear) {
                        $itemName = $gear['name'] . " (iLvl " . $gear['ilevel'] . ")";
                        if (isset($currentLoot[$itemName])) {
                            $currentLoot[$itemName] += 1;
                        } else {
                            $currentLoot[$itemName] = 1;
                        }
                    }

                    $combat->loot_reward = $currentLoot;
                    $combat->save();

                    $lootLog = "☠️ Противник {$enemy->name} повержен! Получено опыта: {$rewards['experience']}, золота: {$rewards['gold']}.";
                    $logs[] = $lootLog;
                }
            } else {
                $log .= "эффект +{$useResult['effect_value']}";
            }

            if ($useResult['mp_spent'] > 0) {
                $log .= " (мана: -{$useResult['mp_spent']}, осталось: {$useResult['mp_remaining']})";
            }

            array_unshift($logs, $log);

            return $logs;
        });

        // Завершение хода игрока, если бой еще идет
        if ($combat->status === 'active' && $combat->participants()->count() > 0) {
            $enemyLogs = $this->endPlayerTurn($combat);
            $result = array_merge($result, $enemyLogs);
        } elseif ($combat->participants()->count() === 0) {
            $this->finishCombat($combat, 'won');
        }

        return [
            'action' => 'ability',
            'logs' => $result,
            'status' => $combat->status,
        ];
    }

    /**
     * Действие: Защита (теперь через общую систему)
     */
    public function performDefense(Combat $combat): array
    {
        $character = $combat->character;
        $class = mb_strtolower($character->class);
        $ability = $this->abilityService->getAbilityForClass($class, 'defense');

        if (!$ability) {
            throw new \Exception("Защитная способность для класса {$class} не найдена.");
        }

        return $this->performAbility($combat, $ability->id);
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
            $enemyStats = $this->enemyService->calculateFinalStats($participant->enemy, $participant->level);
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
            $enemyStats = $this->enemyService->calculateFinalStats($enemy, $participant->level);

            // 1. Попадание (Меткость врага vs Уклонение игрока)
            $enemyAccuracy = $enemyStats['accuracy'] ?? 0;
            $playerEvasion = $charStats->evasion + $dynamic->temp_evasion;

            $rawHitChance = 75 + $enemyAccuracy - $playerEvasion;
            $excessHitChance = max(0, $rawHitChance - 95);
            $hitChance = min(95, $rawHitChance);
            $hitChance = max(5, $hitChance);

            // Бонус к криту от избыточного шанса попадания
            $critBonus = $excessHitChance / 2;
            $baseCritChance = ($enemyStats['luck'] * 0.3);
            $effectiveCritChance = $baseCritChance + $critBonus;

            if (rand(1, 100) > $hitChance) {
                $logs[] = "💨 Враг {$enemy->name} промахнулся (уклонение)!";
                continue;
            }

            // 2. Урон + Криты для мобов
            $baseDamage = rand($enemyStats['min_damage'], $enemyStats['max_damage']);

            // Крит моба на основе удачи + бонус от избыточного попадания
            $critRoll = rand(1, 1000);
            $isCrit = $critRoll <= ($effectiveCritChance * 10);
            $critMultiplier = $isCrit ? 1.5 : 1;
            if ($isCrit) {
                $baseDamage *= 1.5; // Криты мобов чуть слабее (1.5x вместо 2x)
            }

            $totalArmor = $charStats->armor + $dynamic->temp_armor;
            $finalDamage = max(0, round($baseDamage - $totalArmor));

            \Illuminate\Support\Facades\Log::debug('ENEMY_ATTACK_DEBUG', [
                'enemy' => $enemy->name,
                'player' => $character->name,
                'base_damage_range' => $enemyStats['min_damage'] . '-' . $enemyStats['max_damage'],
                'rolled_damage' => $baseDamage / $critMultiplier,
                'raw_hit_chance' => $rawHitChance,
                'excess_hit_chance' => $excessHitChance,
                'crit_bonus' => $critBonus,
                'base_crit_chance' => $baseCritChance,
                'effective_crit_chance' => $effectiveCritChance,
                'crit' => $isCrit,
                'crit_multiplier' => $critMultiplier,
                'player_armor' => $charStats->armor,
                'temp_armor' => $dynamic->temp_armor,
                'total_armor' => $totalArmor,
                'final_damage' => $finalDamage,
            ]);

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

            // HP реген врага после хода
            $enemyHpRegen = $enemyStats['hp_regen'] ?? 0;
            if ($enemyHpRegen > 0 && $participant->current_hp > 0) {
                $participant->current_hp = min($participant->current_hp + $enemyHpRegen, $participant->max_hp);
                \Illuminate\Support\Facades\Log::debug('ENEMY_REGEN_DEBUG', [
                    'enemy' => $enemy->name,
                    'hp_regen' => $enemyHpRegen,
                    'hp_before' => $participant->current_hp - $enemyHpRegen,
                    'hp_after' => $participant->current_hp,
                    'max_hp' => $participant->max_hp,
                ]);
            }
        }

        // Снижение длительности временных эффектов
        if ($dynamic->temp_armor_duration > 0) {
            $dynamic->temp_armor_duration--;
            if ($dynamic->temp_armor_duration === 0) {
                $dynamic->temp_armor = 0;
            }
        }

        if ($dynamic->temp_evasion_duration > 0) {
            $dynamic->temp_evasion_duration--;
            if ($dynamic->temp_evasion_duration === 0) {
                $dynamic->temp_evasion = 0;
            }
        }

        if ($dynamic->current_hp <= 0) {
            $dynamic->current_hp = 1;
            $logs[] = "💀 Вы пали в бою...";
            $dynamic->last_combat_log = implode("\n", $logs);
            $dynamic->save();
            $this->finishCombat($combat, 'lost');
        } else {
            $dynamic->last_combat_log = implode("\n", $logs);
            $dynamic->save();
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
