<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use App\Services\CharacterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterLevelingTest extends TestCase
{
    use RefreshDatabase;

    private CharacterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CharacterService();
    }

    public function test_xp_formula_calculation()
    {
        // XPn = 100 + 30*(n-1) + 10*(n-1)^2
        $this->assertEquals(100, $this->service->calculateXpForLevel(1));
        $this->assertEquals(140, $this->service->calculateXpForLevel(2)); // 100 + 30*1 + 10*1 = 140
        $this->assertEquals(200, $this->service->calculateXpForLevel(3)); // 100 + 30*2 + 10*4 = 100 + 60 + 40 = 200
    }

    public function test_level_up_on_xp_gain()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Leveling Hero',
            'class' => 'Воин',
        ]);

        $this->service->syncStats($character);
        
        // Добавляем 100 XP -> Уровень 2, 3 стат-поинта
        $this->service->addExperience($character, 100);
        
        $character->refresh();
        $this->assertEquals(2, $character->level);
        $this->assertEquals(0, $character->experience);
        $this->assertEquals(3, $character->stat_points);
    }

    public function test_stat_distribution_limit()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Stat Hero',
            'class' => 'Воин',
            'level' => 2,
            'stat_points' => 3,
        ]);

        // На 2 уровне лимит добавочных очков в одну стату = (2-1)*2 = 2.
        
        $this->service->distributeStatPoint($character, 'strength');
        $this->service->distributeStatPoint($character, 'strength');

        $this->assertEquals(2, $character->strength_added);
        $this->assertEquals(1, $character->stat_points);

        // Третий раз в силу должно быть нельзя
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Достигнут предел прокачки этой характеристики для текущего уровня.");
        
        $this->service->distributeStatPoint($character, 'strength');
    }

    public function test_stat_recalculation_after_distribution()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Tank',
            'class' => 'Воин',
            'constitution' => 10, // База 10
            'level' => 2,
            'stat_points' => 1,
        ]);

        $this->service->syncStats($character);
        $this->assertEquals(100, $character->stats->max_hp); // 10 * 10

        // Добавляем 1 в выносливость
        $this->service->distributeStatPoint($character, 'constitution');
        
        $character->refresh();
        $this->assertEquals(110, $character->stats->max_hp); // (10 + 1) * 10
    }
}
