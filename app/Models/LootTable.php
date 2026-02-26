<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LootTable extends Model
{
    protected $fillable = ['name', 'mode', 'chance'];

    public function items()
    {
        return $this->hasMany(LootItem::class);
    }

    public function enemies()
    {
        return $this->belongsToMany(Enemy::class, 'enemy_loot_table');
    }
}
