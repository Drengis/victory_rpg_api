<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterStat extends Model
{
    protected $fillable = [
        'character_id',
        'max_hp',
        'hp_regen',
        'max_mp',
        'mp_regen',
        'physical_damage_bonus',
        'magical_damage_bonus',
        'accuracy',
        'evasion',
        'crit_chance',
        'rare_loot_bonus',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
