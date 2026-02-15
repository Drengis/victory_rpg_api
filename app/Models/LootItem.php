<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LootItem extends Model
{
    protected $fillable = [
        'loot_table_id',
        'item_id',
        'chance',
        'min_quantity',
        'max_quantity',
    ];

    protected $casts = [
        'chance' => 'float',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
    ];

    public function lootTable()
    {
        return $this->belongsTo(LootTable::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
