<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enemy extends Model
{
    protected $fillable = [
        'name',
        'level',
        'min_depth',
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
        'max_depth',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_depth' => 'integer',
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
        'max_depth' => 'integer',
    ];

    public function lootTables()
    {
        return $this->belongsToMany(LootTable::class, 'enemy_loot_table');
    }
}
