<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Combat extends Model
{
    protected $fillable = [
        'character_id',
        'status',
        'current_turn',
        'turn_number',
        'gold_reward',
        'experience_reward',
        'loot_reward',
    ];

    protected $casts = [
        'loot_reward' => 'array',
    ];

    public function character(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function participants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CombatParticipant::class);
    }
}
