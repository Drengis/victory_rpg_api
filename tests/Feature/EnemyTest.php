<?php

namespace Tests\Feature;

use App\Models\Enemy;
use App\Services\EnemyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnemyTest extends TestCase
{
    use RefreshDatabase;

    private EnemyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EnemyService();
    }

    public function test_enemy_stat_calculation()
    {
        // Создаем монстра "Слабый волк"
        $enemy = Enemy::create([
            'name' => 'Weak Wolf',
            'level' => 1,
            'strength' => 10,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 5,
            'luck' => 5,
            'min_damage' => 10,
            'max_damage' => 20,
        ]);

        $stats = $this->service->calculateFinalStats($enemy);

        // Проверка характеристик (HP = Con * 10 = 100)
        $this->assertEquals(100, $stats['max_hp']);
        
        // HP Regen = Con * 0.5 = 5.0
        $this->assertEquals(5.0, $stats['hp_regen']);
        
        // Accuracy = Agi * 2 = 20
        $this->assertEquals(20, $stats['accuracy']);

        // Damage calculation
        // Physical Bonus = Str * 1.5 = 15%
        // Min Damage = 10 * 1.15 = 11.5 -> 12
        // Max Damage = 20 * 1.15 = 23
        $this->assertEquals(15, $stats['physical_damage_bonus']);
        $this->assertEquals(12, $stats['min_damage']);
        $this->assertEquals(23, $stats['max_damage']);
    }

    public function test_enemy_magical_stat_calculation()
    {
        $enemy = Enemy::create([
            'name' => 'Mana Wisp',
            'intelligence' => 20,
            'min_damage' => 5,
            'max_damage' => 10,
        ]);

        $stats = $this->service->calculateFinalStats($enemy);

        // Max MP = Int * 15 = 300
        $this->assertEquals(300, $stats['max_mp']);
        
        // Magical Damage Bonus = Int * 1.5 = 30%
        $this->assertEquals(30, $stats['magical_damage_bonus']);
    }

    public function test_enemy_level_scaling()
    {
        // Создаем моба 11 уровня (рост +10% за уровень, итого +100% за 10 уровней)
        $enemy = Enemy::create([
            'name' => 'High Level Wolf',
            'level' => 11,
            'strength' => 10,
            'agility' => 10,
            'constitution' => 10,
            'min_damage' => 10,
            'max_damage' => 10,
            'scaling_factor' => 0.1,
        ]);

        $stats = $this->service->calculateFinalStats($enemy);

        // Сила должна стать 10 * 2 = 20
        $this->assertEquals(20, $stats['strength']);
        
        // HP = Con(10*2) * 10 = 200
        $this->assertEquals(200, $stats['max_hp']);

        // Damage calculation
        // Scaled Base Damage = 10 * 2 = 20
        // Physical Bonus = Strength(20) * 1.5 = 30%
        // Final Damage = 20 * 1.3 = 26
        $this->assertEquals(26, $stats['min_damage']);
    }
}
