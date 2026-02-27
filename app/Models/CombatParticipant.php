<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\EnemyService;

class CombatParticipant extends Model
{
    protected $fillable = [
        'combat_id',
        'enemy_id',
        'current_hp',
        'current_mp',
        'max_hp',
        'max_mp',
        'level',
        'position',
    ];

    protected $appends = ['enemy_stats'];

    public function combat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Combat::class);
    }

    public function enemy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Enemy::class);
    }

    public function getEnemyStatsAttribute(): array
    {
        if (!$this->enemy) {
            return [];
        }

        $enemyService = app(EnemyService::class);
        return $enemyService->calculateFinalStats($this->enemy, $this->level);
    }
}
