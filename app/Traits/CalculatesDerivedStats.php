<?php

namespace App\Traits;

trait CalculatesDerivedStats
{
    /**
     * Расчитать вторичные характеристики на основе базовых (Сила, Ловкость и т.д.)
     */
    public function getDerivedStats(array $baseStats): array
    {
        return [
            'max_hp' => (int) ($baseStats['constitution'] * 10),
            'hp_regen' => (float) ($baseStats['constitution'] * 0.5),
            'max_mp' => (int) ($baseStats['intelligence'] * 15),
            'mp_regen' => (float) ($baseStats['intelligence'] * 0.2),
            'accuracy' => (float) ($baseStats['agility'] * 2),
            'evasion' => (float) ($baseStats['agility'] * 1),
            'crit_chance' => (float) ($baseStats['luck'] * 0.3),
            'rare_loot_bonus' => (float) ($baseStats['luck'] * 0.5),
        ];
    }
}
