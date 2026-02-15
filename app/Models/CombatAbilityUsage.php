<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CombatAbilityUsage extends Model
{
    protected $table = 'combat_ability_usage';
    
    protected $fillable = [
        'combat_id',
        'ability_id',
        'turn_used',
    ];

    protected $casts = [
        'turn_used' => 'integer',
    ];

    /**
     * Отношение к бою
     */
    public function combat()
    {
        return $this->belongsTo(Combat::class);
    }

    /**
     * Отношение к способности
     */
    public function ability()
    {
        return $this->belongsTo(ClassAbility::class, 'ability_id');
    }
}
