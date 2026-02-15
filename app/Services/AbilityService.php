<?php

namespace App\Services;

use App\Models\ClassAbility;
use App\Models\CombatAbilityUsage;
use App\Models\Combat;
use App\Models\Character;

class AbilityService
{
    /**
     * Получить способность для класса персонажа
     */
    public function getAbilityForClass(string $class, string $abilityType = 'defense'): ?ClassAbility
    {
        return ClassAbility::where('class', mb_strtolower($class))
            ->where('ability_type', $abilityType)
            ->first();
    }

    /**
     * Проверить доступность способности
     */
    public function canUseAbility(ClassAbility $ability, Combat $combat, Character $character): array
    {
        $dynamic = $character->dynamicStats;
        
        // 1. Проверка маны
        if ($ability->mp_cost > $dynamic->current_mp) {
            return [
                'can_use' => false,
                'reason' => "Недостаточно маны. Требуется: {$ability->mp_cost}, доступно: {$dynamic->current_mp}"
            ];
        }
        
        // 2. Проверка лимита использований за бой
        if ($ability->max_uses_per_combat > 0) {
            $usedCount = CombatAbilityUsage::where('combat_id', $combat->id)
                ->where('ability_id', $ability->id)
                ->count();
                
            if ($usedCount >= $ability->max_uses_per_combat) {
                return [
                    'can_use' => false,
                    'reason' => 'Способность уже использована в этом бою'
                ];
            }
        }
        
        return ['can_use' => true];
    }

    /**
     * Использовать способность
     */
    public function useAbility(ClassAbility $ability, Combat $combat, Character $character, array $totalStats): array
    {
        $dynamic = $character->dynamicStats;
        
        // Вычисление эффекта по формуле
        $effectValue = $ability->calculateEffect($totalStats);
        
        // Применение эффекта
        switch ($ability->effect_type) {
            case 'temp_armor':
                $dynamic->temp_armor = $effectValue;
                break;
            case 'temp_evasion':
                $dynamic->temp_evasion = $effectValue;
                break;
            case 'barrier':
                $dynamic->barrier_hp = round($effectValue);
                break;
        }
        
        // Списание маны
        $dynamic->current_mp -= $ability->mp_cost;
        $dynamic->save();
        
        // Запись использования
        CombatAbilityUsage::create([
            'combat_id' => $combat->id,
            'ability_id' => $ability->id,
            'turn_used' => $combat->turn_number,
        ]);
        
        return [
            'success' => true,
            'ability_name' => $ability->ability_name,
            'effect_value' => $effectValue,
            'mp_spent' => $ability->mp_cost,
            'mp_remaining' => $dynamic->current_mp,
        ];
    }
}
