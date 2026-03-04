<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'target_value',
        'requirements',
        'rewards',
    ];

    protected $casts = [
        'requirements' => 'array',
        'rewards' => 'array',
        'target_value' => 'integer',
    ];

    public function characters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'character_quests')
            ->withPivot('current_value', 'status')
            ->withTimestamps();
    }
}
