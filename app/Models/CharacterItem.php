<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterItem extends Model
{
    protected $fillable = [
        'character_id',
        'item_id',
        'ilevel',
        'slot',
        'is_equipped',
        'quantity',
    ];

    protected $casts = [
        'is_equipped' => 'boolean',
        'ilevel' => 'integer',
        'quantity' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Добавление характеристик предмета с учетом iLvl в JSON
     */
    public function toArray()
    {
        $array = parent::toArray();
        if ($this->relationLoaded('item') && $this->item) {
            $array['item']['display_stats'] = $this->item->getBonusesList($this->ilevel);
        }
        return $array;
    }
}
