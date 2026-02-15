<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'type',
        'quality',
        'scaling_factor',
        'strength',
        'agility',
        'constitution',
        'intelligence',
        'luck',
        'min_damage',
        'max_damage',
    ];

    protected $casts = [
        'quality' => 'integer',
        'scaling_factor' => 'float',
        'strength' => 'integer',
        'agility' => 'integer',
        'constitution' => 'integer',
        'intelligence' => 'integer',
        'luck' => 'integer',
        'min_damage' => 'integer',
        'max_damage' => 'integer',
    ];

    // Коэффициенты редкости
    const QUALITY_COMMON = 1;
    const QUALITY_UNCOMMON = 2;
    const QUALITY_RARE = 3;
    const QUALITY_EPIC = 4;
    const QUALITY_LEGENDARY = 5;

    const QUALITY_MULTIPLIERS = [
        self::QUALITY_COMMON => 1.0,
        self::QUALITY_UNCOMMON => 1.2,
        self::QUALITY_RARE => 1.5,
        self::QUALITY_EPIC => 1.8,
        self::QUALITY_LEGENDARY => 2.2,
    ];

    /**
     * Получить реальное значение характеристики с учетом iLvl и редкости
     */
    public function getBonus(string $stat, int $ilevel = 1): int
    {
        $baseValue = $this->{$stat} ?? 0;
        
        // 1. Масштабирование по уровню (базовая прогрессия)
        $scaledBase = $baseValue * (1 + ($ilevel - 1) * $this->scaling_factor);
        
        // 2. Множитель редкости применяется к отмасштабированной базе
        $multiplier = self::QUALITY_MULTIPLIERS[$this->quality] ?? 1.0;
        
        return (int) round($scaledBase * $multiplier);
    }
}
