<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Character extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'class',
        'level',
        'experience',
        'gold',
        'stat_points',
        'strength',
        'agility',
        'constitution',
        'intelligence',
        'luck',
        'strength_added',
        'agility_added',
        'constitution_added',
        'intelligence_added',
        'luck_added',
    ];

    protected $attributes = [
        'level' => 1,
        'experience' => 0,
        'gold' => 0,
        'stat_points' => 0,
        'strength' => 5,
        'agility' => 5,
        'constitution' => 5,
        'intelligence' => 5,
        'luck' => 5,
        'strength_added' => 0,
        'agility_added' => 0,
        'constitution_added' => 0,
        'intelligence_added' => 0,
        'luck_added' => 0,
    ];

    protected $casts = [
        'level' => 'integer',
        'experience' => 'integer',
        'gold' => 'integer',
        'stat_points' => 'integer',
    ];

    public function stats(): HasOne
    {
        return $this->hasOne(CharacterStat::class);
    }

    public function dynamicStats(): HasOne
    {
        return $this->hasOne(CharacterDynamicStat::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CharacterItem::class);
    }

    public function equippedItems(): HasMany
    {
        return $this->hasMany(CharacterItem::class)->where('is_equipped', true);
    }

    public function getNextLevelXpAttribute(): int
    {
        $level = $this->level;
        $n_minus_1 = $level - 1;
        return 100 + (30 * $n_minus_1) + (10 * ($n_minus_1 ** 2));
    }

    public function getXpPercentageAttribute(): float
    {
        $nextXp = $this->next_level_xp;
        if ($nextXp <= 0) return 0;
        return min(100, ($this->experience / $nextXp) * 100);
    }

    protected $appends = ['next_level_xp', 'xp_percentage'];
}
