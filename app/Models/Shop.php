<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = ['name', 'description'];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'shop_items')
            ->withPivot(['price_override', 'ilevel'])
            ->withTimestamps();
    }
}
