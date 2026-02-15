<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use App\Services\CharacterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterStatsTest extends TestCase
{
    use RefreshDatabase;

    private CharacterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CharacterService();
    }

    public function test_warrior_stats_calculation()
    {
        $user = User::factory()->create();
        $character = new Character([
            'user_id' => $user->id,
            'class' => 'Воин',
            'strength' => 20,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        $stats = $this->service->calculateFinalStats($character);

        // Воин: +10% Сила (22), -10% Интеллект (9)
        // Accuracy: Agility(10)*2 + Strength(22)*0.5 = 20 + 11 = 31
        $this->assertEquals(31, $stats['accuracy']);
        
        // Physical Damage: Str(22)*2 = 44%
        $this->assertEquals(44, $stats['physical_damage_bonus']);
    }

    public function test_mage_stats_calculation()
    {
        $user = User::factory()->create();
        $character = new Character([
            'user_id' => $user->id,
            'class' => 'Маг',
            'strength' => 10,
            'agility' => 10,
            'constitution' => 20,
            'intelligence' => 20,
            'luck' => 10,
        ]);

        $stats = $this->service->calculateFinalStats($character);

        // Маг: +10% Интеллект (22), -10% Телосложение (18)
        // Mana: Intel(22) * 15 = 330
        $this->assertEquals(330, $stats['max_mp']);
        
        // Accuracy: Agility(10)*2 + Intel(22)*0.5 = 20 + 11 = 31
        $this->assertEquals(31, $stats['accuracy']);
        
        // Magical Damage: Int(22)*2 = 44%
        $this->assertEquals(44, $stats['magical_damage_bonus']);
    }
}
