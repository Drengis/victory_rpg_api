<?php

namespace App\Services;

use App\Models\Character;
use App\Services\Core\BaseService;

class CharacterService extends BaseService
{
    protected function getModel(): string
    {
        return Character::class;
    }

    /**
     * Расчитать финальные характеристики персонажа
     * @param Character $character
     * @return array
     */
    public function calculateFinalStats(Character $character): array
    {
        $baseStats = [
            'strength' => $character->strength,
            'agility' => $character->agility,
            'constitution' => $character->constitution,
            'intelligence' => $character->intelligence,
            'luck' => $character->luck,
        ];

        $modifiers = $this->getClassModifiers($character->class);
        
        $modifiedStats = [];
        foreach ($baseStats as $stat => $value) {
            $mod = $modifiers[$stat] ?? 0;
            $modifiedStats[$stat] = round($value * (1 + $mod / 100));
        }

        return [
            'base_stats' => $baseStats,
            'class_modifiers' => $modifiers,
            'final_stats' => $modifiedStats,
            'derived_stats' => $this->calculateDerivedStats($character, $modifiedStats),
        ];
    }

    /**
     * Получить модификаторы для класса
     */
    private function getClassModifiers(string $class): array
    {
        return match (mb_strtolower($class)) {
            'воин' => ['strength' => 10, 'intelligence' => -10],
            'лучник' => ['agility' => 10, 'strength' => -10],
            'маг' => ['intelligence' => 10, 'constitution' => -10],
            default => [],
        };
    }

    /**
     * Расчитать производные параметры
     */
    private function calculateDerivedStats(Character $character, array $finalStats): array
    {
        $mainStatBonus = $this->getMainStatBonus($character->class, $finalStats);

        return [
            'hp' => $finalStats['constitution'] * 10,
            'hp_regen' => $finalStats['constitution'] * 0.5,
            'mana' => $finalStats['intelligence'] * 15,
            'mana_regen' => $finalStats['intelligence'] * 0.2,
            
            // Урон (Damage): 
            // Сила дает 1% к физ. урону всем. Воин получает еще +1% от силы (итого 2%).
            // Ловкость дает +1% к физ. урону для Лучника.
            'physical_damage_bonus' => ($finalStats['strength'] * 1) 
                                        + ($this->isMainStat($character->class, 'strength') ? $finalStats['strength'] * 1 : 0)
                                        + ($this->isMainStat($character->class, 'agility') ? $finalStats['agility'] * 1 : 0),
                                        
            // Интеллект дает 1% к маг. урону всем. Маг получает еще +1% (итого 2%).
            'magical_damage_bonus' => ($finalStats['intelligence'] * 1) 
                                        + ($this->isMainStat($character->class, 'intelligence') ? $finalStats['intelligence'] * 1 : 0),
            
            // Попадание (Accuracy): Ловкость +1.5%. Ключевой стат еще +0.5%.
            'accuracy' => ($finalStats['agility'] * 1.5) + ($mainStatBonus['accuracy'] ?? 0),
            
            // Уклонение (Evasion): Ловкость +1%, Удача +0.5.
            'evasion' => ($finalStats['agility'] * 1.0) + ($finalStats['luck'] * 0.5),
            
            // Крит: Ловкость +0.3%, Удача +0.1%.
            'crit_chance' => ($finalStats['agility'] * 0.3) + ($finalStats['luck'] * 0.1),
            
            // Редкий лут: Удача +1% (множитель 0.01)
            'rare_loot_bonus' => $finalStats['luck'] * 0.01,
        ];
    }

    /**
     * Проверка, является ли характеристика основной для класса
     */
    private function isMainStat(string $class, string $stat): bool
    {
        $mainStats = [
            'воин' => 'strength',
            'лучник' => 'agility',
            'маг' => 'intelligence',
        ];

        return ($mainStats[mb_strtolower($class)] ?? '') === $stat;
    }

    /**
     * Ключевая характеристика дает дополнительные бонусы
     */
    private function getMainStatBonus(string $class, array $finalStats): array
    {
        $class = mb_strtolower($class);
        $mainStatValue = 0;

        if ($class === 'воин') $mainStatValue = $finalStats['strength'];
        elseif ($class === 'лучник') $mainStatValue = $finalStats['agility'];
        elseif ($class === 'маг') $mainStatValue = $finalStats['intelligence'];

        return [
            'damage' => $mainStatValue * 1.0, // +1% к урону
            'accuracy' => $mainStatValue * 0.5, // +0.5% к попаданию
        ];
    }
}
