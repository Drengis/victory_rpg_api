<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CombatParticipant extends Model
{
    protected $fillable = [
        'combat_id',
        'enemy_id',
        'current_hp',
        'current_mp',
        'level',
        'position',
    ];

    public function combat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Combat::class);
    }

    public function enemy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }
}
