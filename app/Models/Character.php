<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Character extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'class',
        'strength',
        'agility',
        'constitution',
        'intelligence',
        'luck',
    ];

    public function stats(): HasOne
    {
        return $this->hasOne(CharacterStat::class);
    }

    public function dynamicStats(): HasOne
    {
        return $this->hasOne(CharacterDynamicStat::class);
    }
}
