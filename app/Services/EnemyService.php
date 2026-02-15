<?php

namespace App\Services;

use App\Models\Enemy;
use App\Services\Core\BaseService;
use App\Traits\CalculatesDerivedStats;

class EnemyService extends BaseService
{
    use CalculatesDerivedStats;

    protected function getModel(): string
    {
        return Enemy::class;
    }

    /**
     * Расчитать полные характеристики монстра
     */
    public function calculateFinalStats(Enemy $enemy): array
    {
        $level = $enemy->level ?? 1;
        $scaling = $enemy->scaling_factor ?? 0.1;

        // Масштабируем статы от уровня: Base * (1 + (Lvl-1) * Factor)
        $scaleMult = 1 + ($level - 1) * $scaling;

        $baseStats = [
            'strength' => (int) round($enemy->strength * $scaleMult),
            'agility' => (int) round($enemy->agility * $scaleMult),
            'constitution' => (int) round($enemy->constitution * $scaleMult),
            'intelligence' => (int) round($enemy->intelligence * $scaleMult),
            'luck' => (int) round($enemy->luck * $scaleMult),
        ];

        // Получаем вторичные характеристики из трейта
        $derived = $this->getDerivedStats($baseStats);

        // Расчет бонусов урона (упрощенный для мобов без классов)
        $physicalBonus = round($baseStats['strength'] * 1.5);
        $magicalBonus = round($baseStats['intelligence'] * 1.5);

        // Масштабируем базовый урон моба тоже
        $baseMin = round($enemy->min_damage * $scaleMult);
        $baseMax = round($enemy->max_damage * $scaleMult);

        // Применяем бонусы к отмасштабированному урону
        $finalMin = round($baseMin * (1 + $physicalBonus / 100));
        $finalMax = round($baseMax * (1 + $physicalBonus / 100));

        // Масштабируем награды: Base * (1 + (Lvl-1) * Factor)
        $xpReward = round($enemy->base_experience * $scaleMult);
        $goldReward = round($enemy->base_gold * $scaleMult);

        return array_merge($baseStats, $derived, [
            'physical_damage_bonus' => (int) $physicalBonus,
            'magical_damage_bonus' => (int) $magicalBonus,
            'min_damage' => (int) $finalMin,
            'max_damage' => (int) $finalMax,
            'experience_reward' => (int) $xpReward,
            'gold_reward' => (int) $goldReward,
        ]);
    }
}
