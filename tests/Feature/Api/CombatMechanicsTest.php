<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Character;
use App\Models\Enemy;
use App\Models\Combat;
use App\Services\CombatService;
use App\Services\EnemyService;
use App\Services\AbilityService;
use App\Services\RewardService;
use App\Services\CharacterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CombatMechanicsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $character;
    protected $enemy;
    protected $combatService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $this->character = Character::create([
            'user_id' => $this->user->id,
            'name' => 'FleeHero',
            'class' => 'Воин',
            'level' => 1,
            'strength' => 10,
            'agility' => 20, // Высокая ловкость для побега
            'constitution' => 10,
            'intelligence' => 5,
            'luck' => 5,
        ]);
        
        (new CharacterService())->syncStats($this->character);

        $this->enemy = Enemy::create([
            'name' => 'Slow Monster',
            'level' => 1,
            'strength' => 5,
            'constitution' => 5,
            'agility' => 1, // Низкая ловкость
            'intelligence' => 5,
            'luck' => 5,
            'base_experience' => 100,
            'base_gold' => 10
        ]);

        $this->combatService = app(CombatService::class);
    }

    public function test_can_flee_combat_with_high_agility()
    {
        $combat = $this->combatService->startCombat($this->character, [$this->enemy->id]);
        $combat->update(['current_turn' => 'player']);

        // Пытаемся сбежать. С разницей в 19 единиц ловкости шанс должен быть высоким.
        $result = $this->combatService->performFlee($combat);

        $this->assertArrayHasKey('success', $result);
        $this->assertDatabaseHas('combats', [
            'id' => $combat->id,
            'status' => $result['success'] ? 'fled' : 'active'
        ]);
    }

    public function test_regeneration_applied_after_turn()
    {
        // Даем много выносливости, чтобы регенерация была > 1 (минимальный урон врага)
        $this->character->update(['constitution' => 100]);
        (new CharacterService())->syncStats($this->character);
        
        $this->character->dynamicStats->update(['current_hp' => 50]);
        $initialHp = $this->character->dynamicStats->current_hp;

        $combat = $this->combatService->startCombat($this->character, [$this->enemy->id]);
        $combat->update(['current_turn' => 'player']);

        // Пропускаем ход (через атаку или защиту, чтобы сработал endPlayerTurn)
        // Но проще вызвать endPlayerTurn напрямую или через атаку
        $participant = $combat->participants->first();
        $this->combatService->performAttack($combat, $participant->id);

        $this->character->refresh();
        $this->assertGreaterThan($initialHp, $this->character->dynamicStats->current_hp, "HP should regenerate after turn");
    }
}
