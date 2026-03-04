<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterQuest extends Model
{
    protected $fillable = [
        'character_id',
        'quest_id',
        'current_value',
        'status',
    ];

    public function character(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function quest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }
}
