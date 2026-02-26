<?php

namespace App\Services;

use App\Models\ClassAbility;
use App\Models\CombatAbilityUsage;
use App\Models\Combat;
use App\Models\Character;

class AbilityService
{
    /**
     * Получить все доступные способности для персонажа (по классу и уровню)
     */
    public function getAvailableAbilities(Character $character): \Illuminate\Support\Collection
    {
        return $character->abilities;
    }

    /**
     * Получить все способности класса, которые можно купить
     */
    public function getAbilitiesToBuy(Character $character): \Illuminate\Support\Collection
    {
        $ownedIds = $character->abilities()->pluck('class_abilities.id')->toArray();

        return ClassAbility::where('class', mb_strtolower($character->class))
            ->whereNotIn('id', $ownedIds)
            ->get();
    }

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
        
        // 1. Проверка разблокировки
        if (!$character->abilities()->where('ability_id', $ability->id)->exists()) {
            return [
                'can_use' => false,
                'reason' => "Способность не изучена"
            ];
        }

        // 2. Проверка уровня
        if ($character->level < $ability->level_required) {
            return [
                'can_use' => false,
                'reason' => "Ваш уровень слишком мал. Требуется: {$ability->level_required}"
            ];
        }

        // 2. Проверка маны
        if ($ability->mp_cost > $dynamic->current_mp) {
            return [
                'can_use' => false,
                'reason' => "Недостаточно маны. Требуется: {$ability->mp_cost}, доступно: {$dynamic->current_mp}"
            ];
        }
        
        // 3. Проверка лимита использований за бой
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
    public function useAbility(ClassAbility $ability, Combat $combat, Character $character, array $totalStats, ?int $targetId = null): array
    {
        $dynamic = $character->dynamicStats;
        $effectValue = $ability->calculateEffect($totalStats);
        $damageDealt = 0;
        
        // Применение эффекта
        switch ($ability->effect_type) {
            case 'temp_armor':
                $dynamic->temp_armor = $effectValue;
                $dynamic->temp_armor_duration = $ability->duration;
                break;
            case 'temp_evasion':
                $dynamic->temp_evasion = $effectValue;
                $dynamic->temp_evasion_duration = $ability->duration;
                break;
            case 'barrier':
                $dynamic->barrier_hp = round($effectValue);
                // Барьер не имеет длительности в ходах, он висит пока не собьют
                break;
            case 'deal_damage':
                if ($targetId) {
                    $participant = $combat->participants()->find($targetId);
                    if ($participant) {
                        $damageDealt = round($effectValue);
                        $participant->current_hp = max(0, $participant->current_hp - $damageDealt);
                        $participant->save();
                    }
                }
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
            'damage_dealt' => $damageDealt,
            'mp_spent' => $ability->mp_cost,
            'mp_remaining' => $dynamic->current_mp,
        ];
    }
}
