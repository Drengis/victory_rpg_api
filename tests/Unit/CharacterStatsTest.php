<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Services\CharacterService;
use PHPUnit\Framework\TestCase;

class CharacterStatsTest extends TestCase
{
    private CharacterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CharacterService();
    }

    public function test_warrior_stats_calculation()
    {
        $character = new Character([
            'class' => 'Воин',
            'strength' => 20,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        $stats = $this->service->calculateFinalStats($character);

        // Воин: +10% Сила, -10% Интеллект
        $this->assertEquals(22, $stats['final_stats']['strength']); // 20 * 1.1
        $this->assertEquals(9, $stats['final_stats']['intelligence']);  // 10 * 0.9
        
        // Derived stats for Warrior
        // Accuracy: Agility(10)*1.5 + StrengthBonus(22*0.5) = 15 + 11 = 26
        $this->assertEquals(26, $stats['derived_stats']['accuracy']);
        // Physical Damage: Str(22)*1 + Str(22)*1 = 44
        $this->assertEquals(44, $stats['derived_stats']['physical_damage_bonus']);
    }

    public function test_mage_stats_calculation()
    {
        $character = new Character([
            'class' => 'Маг',
            'strength' => 10,
            'agility' => 10,
            'constitution' => 20,
            'intelligence' => 20,
            'luck' => 10,
        ]);

        $stats = $this->service->calculateFinalStats($character);

        // Маг: +10% Интеллект, -10% Телосложение
        $this->assertEquals(22, $stats['final_stats']['intelligence']); // 20 * 1.1
        $this->assertEquals(18, $stats['final_stats']['constitution']); // 20 * 0.9
        
        // Derived stats for Mage
        // Mana: Intel(22) * 15 = 330
        $this->assertEquals(330, $stats['derived_stats']['mana']);
        // Accuracy: Agility(10)*1.5 + IntelBonus(22*0.5) = 15 + 11 = 26
        $this->assertEquals(26, $stats['derived_stats']['accuracy']);
        // Magical Damage: Int(22)*1 + Int(22)*1 = 44
        $this->assertEquals(44, $stats['derived_stats']['magical_damage_bonus']);
    }
}
