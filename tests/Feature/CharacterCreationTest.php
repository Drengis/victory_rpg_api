<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use App\Services\CharacterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterCreationTest extends TestCase
{
    use RefreshDatabase;

    private CharacterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CharacterService();
    }

    public function test_warrior_creation_stats()
    {
        $user = User::factory()->create();
        $character = $this->service->createCharacter([
            'user_id' => $user->id,
            'name' => 'Conan',
            'class' => 'Воин',
        ]);

        // База 5. Воин: +10% Strength, -10% Intelligence
        // Strength: 5 * 1.1 = 5.5 -> round(6)? Или round(5.5)? 
        // В коде round($value * (1 + $mod / 100)). 5 * 1.1 = 5.5 -> 6.0
        // Intelligence: 5 * 0.9 = 4.5 -> 5.0 (PHP round(4.5) = 5.0)
        
        $this->assertEquals(1, $character->level);
        $this->assertEquals(0, $character->stat_points);
        
        $stats = $character->stats;
        // HP = constitution * 10 = 5 * 10 = 50
        $this->assertEquals(50, $stats->max_hp);
        
        // Physical Damage Bonus for Warrior: Str * 2 = 6 * 2 = 12%
        $this->assertEquals(12, $stats->physical_damage_bonus);
        
        // Dynamic stats should be initialized
        $this->assertEquals(50, $character->dynamicStats->current_hp);
    }

    public function test_archer_creation_stats()
    {
        $user = User::factory()->create();
        $character = $this->service->createCharacter([
            'user_id' => $user->id,
            'name' => 'Legolas',
            'class' => 'Лучник',
        ]);

        // База 5. Лучник: +10% Agility, -10% Strength
        // Agility: 5.5 -> 6
        // Strength: 4.5 -> 5
        
        $stats = $character->stats;
        // Accuracy for Archer: Agi(6) * 4 = 24%
        $this->assertEquals(24, $stats->accuracy);
        // Damage bonus for Archer: Str(1%) + Agi(1%) = 5 + 6 = 11%
        $this->assertEquals(11, $stats->physical_damage_bonus);
    }

    public function test_mage_creation_stats()
    {
        $user = User::factory()->create();
        $character = $this->service->createCharacter([
            'user_id' => $user->id,
            'name' => 'Gandalf',
            'class' => 'Маг',
        ]);

        // База 5. Маг: +10% Intelligence, -10% Constitution
        // Intelligence: 5.5 -> 6
        // Constitution: 4.5 -> 5
        
        $stats = $character->stats;
        // Mana = Intelligence * 15 = 6 * 15 = 90
        $this->assertEquals(90, $stats->max_mp);
        // Magical damage bonus for Mage: Int * 2 = 6 * 2 = 12%
        $this->assertEquals(12, $stats->magical_damage_bonus);
    }

    public function test_invalid_class_creation()
    {
        $user = User::factory()->create();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Невалидный класс персонажа.");

        $this->service->createCharacter([
            'user_id' => $user->id,
            'name' => 'Glitch',
            'class' => 'Паладин',
        ]);
    }
}
