<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enemy extends Model
{
    protected $fillable = [
        'name',
        'level',
        'strength',
        'agility',
        'constitution',
        'intelligence',
        'luck',
        'scaling_factor',
        'min_damage',
        'max_damage',
        'base_experience',
        'base_gold',
        'loot_table_id',
    ];

    protected $casts = [
        'level' => 'integer',
        'strength' => 'integer',
        'agility' => 'integer',
        'constitution' => 'integer',
        'intelligence' => 'integer',
        'luck' => 'integer',
        'scaling_factor' => 'float',
        'min_damage' => 'integer',
        'max_damage' => 'integer',
        'base_experience' => 'integer',
        'base_gold' => 'integer',
        'loot_table_id' => 'integer',
    ];

    public function lootTable()
    {
        return $this->belongsTo(LootTable::class);
    }
}
