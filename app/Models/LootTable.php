<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LootTable extends Model
{
    protected $fillable = ['name'];

    public function items()
    {
        return $this->hasMany(LootItem::class);
    }

    public function enemies()
    {
        return $this->hasMany(Enemy::class);
    }
}
