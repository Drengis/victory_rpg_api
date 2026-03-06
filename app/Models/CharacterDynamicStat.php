<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterDynamicStat extends Model
{
    protected $fillable = [
        'character_id',
        'current_hp',
        'current_mp',
        'is_in_combat',
        'last_regen_at',
        'temp_armor',
        'temp_armor_duration',
        'temp_evasion',
        'temp_evasion_duration',
        'barrier_hp',
        'last_combat_log',
        'enemies_defeated_at_depth',
        'effects',
    ];

    protected $attributes = [
        'effects' => '[]',
    ];

    protected $casts = [
        'is_in_combat' => 'boolean',
        'last_regen_at' => 'datetime',
        'temp_armor_duration' => 'integer',
        'temp_evasion_duration' => 'integer',
        'effects' => 'array',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
