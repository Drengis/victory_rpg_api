<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassAbility extends Model
{
    protected $fillable = [
        'class',
        'ability_name',
        'ability_type',
        'mp_cost',
        'max_uses_per_combat',
        'cooldown_turns',
        'effect_type',
        'effect_formula',
        'description',
    ];

    protected $casts = [
        'mp_cost' => 'integer',
        'max_uses_per_combat' => 'integer',
        'cooldown_turns' => 'integer',
    ];

    /**
     * Отношение к использованиям способности
     */
    public function usages()
    {
        return $this->hasMany(CombatAbilityUsage::class, 'ability_id');
    }

    /**
     * Вычислить эффект способности для персонажа
     */
    public function calculateEffect(array $stats): float
    {
        // Парсим формулу и вычисляем значение
        $formula = $this->effect_formula;
        
        // Простой парсер формул вида "stat * multiplier" или просто "stat"
        if (preg_match('/^(\w+)\s*\*\s*([\d.]+)$/', $formula, $matches)) {
            $stat = $matches[1];
            $multiplier = (float) $matches[2];
            return ($stats[$stat] ?? 0) * $multiplier;
        } elseif (preg_match('/^(\w+)$/', $formula, $matches)) {
            $stat = $matches[1];
            return $stats[$stat] ?? 0;
        }
        
        return 0;
    }
}
