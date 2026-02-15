<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'type',
        'quality',
        'base_price',
        'scaling_factor',
        'strength',
        'agility',
        'constitution',
        'intelligence',
        'luck',
        'min_damage',
        'max_damage',
        'required_class',
    ];

    protected $casts = [
        'quality' => 'integer',
        'base_price' => 'integer',
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
        self::QUALITY_UNCOMMON => 3.0,
        self::QUALITY_RARE => 10.0,
        self::QUALITY_EPIC => 25.0,
        self::QUALITY_LEGENDARY => 100.0,
    ];

    /**
     * Получить реальное значение характеристики с учетом iLvl и редкости
     */
    public function getBonus(string $stat, int $ilevel = 1): int
    {
        $baseValue = $this->{$stat} ?? 0;
        
        // 1. Масштабирование по уровню (базовая прогрессия)
        $scaledBase = $baseValue * (1 + ($ilevel - 1) * $this->scaling_factor);
        
        // 2. Множитель редкости ДЛЯ СТАТОВ (отдельная логика, оставим старую для баланса статов)
        $statMultipliers = [
            self::QUALITY_COMMON => 1.0,
            self::QUALITY_UNCOMMON => 1.2,
            self::QUALITY_RARE => 1.5,
            self::QUALITY_EPIC => 1.8,
            self::QUALITY_LEGENDARY => 2.2,
        ];

        $multiplier = $statMultipliers[$this->quality] ?? 1.0;
        
        return (int) round($scaledBase * $multiplier);
    }

    /**
     * Рассчитать цену предмета с учетом iLvl и редкости
     */
    public function calculatePrice(int $ilevel = 1): int
    {
        // 1. Базовая цена масштабируется по уровню (+20% за уровень)
        $scaledPrice = $this->base_price * (1 + ($ilevel - 1) * 0.2);
        
        // 2. Множитель редкости для экономики (x1, x3 и т.д.)
        $multiplier = self::QUALITY_MULTIPLIERS[$this->quality] ?? 1.0;
        
        return (int) round($scaledPrice * $multiplier);
    }

    /**
     * Можно ли купить этот предмет в обычном магазине
     */
    public function isPurchasable(): bool
    {
        return $this->quality <= self::QUALITY_UNCOMMON;
    }
}
