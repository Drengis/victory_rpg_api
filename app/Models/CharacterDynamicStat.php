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
    ];

    protected $casts = [
        'is_in_combat' => 'boolean',
        'last_regen_at' => 'datetime',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
