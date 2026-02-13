<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
