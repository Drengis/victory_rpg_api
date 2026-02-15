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

    public function __construct(EnemyService $enemyService, AbilityService $abilityService)
    {
        $this->enemyService = $enemyService;
        $this->abilityService = $abilityService;
    }

    /**
     * Начало боя
     */
    public function startCombat(Character $character, array $enemyIds): Combat
    {
        return DB::transaction(function () use ($character, $enemyIds) {
            // 1. Создаем запись боя
            $combat = Combat::create([
                'character_id' => $character->id,
                'status' => 'active',
                'current_turn' => rand(1, 100) <= 50 ? 'player' : 'enemies',
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

            $damage = rand($stats->min_damage, $stats->max_damage);
            $finalDamage = max(1, $damage);

            $participant->current_hp -= $finalDamage;
            $participant->save();

            $log = "Вы нанесли {$finalDamage} урона противнику {$participant->enemy->name}.";
            
            if ($participant->current_hp <= 0) {
                $participant->delete();
                $log .= " Противник повержен!";
            }

            if ($combat->participants()->count() === 0) {
                $this->finishCombat($combat, 'won');
            } else {
                $this->endPlayerTurn($combat);
            }

            return [
                'action' => 'attack',
                'damage' => $finalDamage,
                'log' => $log,
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

        $log = DB::transaction(function () use ($combat) {
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
            
            $log = "{$result['ability_name']}: эффект {$result['effect_value']}";
            if ($result['mp_spent'] > 0) {
                $log .= ". Потрачено маны: {$result['mp_spent']} (осталось: {$result['mp_remaining']})";
            }
            
            return $log;
        });

        // Ход врагов вызываем ПОСЛЕ завершения транзакции
        $this->endPlayerTurn($combat);

        return [
            'action' => 'defense',
            'log' => $log,
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

        $success = rand(1, 100) <= 50;

        if ($success) {
            $this->finishCombat($combat, 'fled');
            return [
                'success' => true,
                'log' => 'Вы успешно сбежали с поля боя!',
                'status' => 'fled'
            ];
        }

        $log = "Побег не удался! Враги окружают вас.";
        $this->endPlayerTurn($combat);
        
        return [
            'success' => false,
            'log' => $log,
            'status' => 'active'
        ];
    }

    /**
     * Завершение хода игрока
     */
    public function endPlayerTurn(Combat $combat): void
    {
        $combat->update(['current_turn' => 'enemies']);
        $this->processEnemiesTurn($combat);
    }

    /**
     * Логика хода врагов
     */
    public function processEnemiesTurn(Combat $combat): void
    {
        $character = $combat->character;
        $dynamic = $character->dynamicStats()->first();
        $charStats = $character->stats;
        $logs = [];

        foreach ($combat->participants as $participant) {
            $enemy = $participant->enemy;
            $enemyStats = $this->enemyService->calculateFinalStats($enemy);
            
            // 1. Уклонение
            $evasionChance = $charStats->evasion + $dynamic->temp_evasion;
            if (rand(1, 1000) <= ($evasionChance * 10)) {
                $logs[] = "Враг {$enemy->name} промахнулся!";
                continue;
            }

            // 2. Урон
            $damage = rand($enemyStats['min_damage'], $enemyStats['max_damage']);
            $totalArmor = $charStats->armor + $dynamic->temp_armor;
            $finalDamage = max(1, $damage - $totalArmor);

            // 3. Барьер
            if ($dynamic->barrier_hp > 0) {
                $absorbed = min($dynamic->barrier_hp, $finalDamage);
                $dynamic->barrier_hp -= $absorbed;
                $finalDamage -= $absorbed;
                $logs[] = "Магический барьер поглотил {$absorbed} урона.";
            }

            if ($finalDamage > 0) {
                $dynamic->current_hp -= $finalDamage;
                $logs[] = "Враг {$enemy->name} нанес вам {$finalDamage} урона.";
            }
        }

        // Сброс временных бонусов (барьер НЕ сбрасывается, он расходуется постепенно)
        $dynamic->temp_armor = 0;
        $dynamic->temp_evasion = 0;
        $dynamic->last_combat_log = implode("\n", $logs);
        $dynamic->save();

        if ($dynamic->current_hp <= 0) {
            $this->finishCombat($combat, 'lost');
        } else {
            $combat->increment('turn_number');
            $combat->update(['current_turn' => 'player']);
        }
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
