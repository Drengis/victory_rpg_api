<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Character;
use App\Models\Enemy;
use App\Models\Combat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CombatApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $character;
    protected $enemy;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        
        $this->character = Character::create([
            'user_id' => $this->user->id,
            'name' => 'TestHero',
            'class' => 'Воин',
            'level' => 1,
            'strength' => 10,
            'agility' => 5,
            'constitution' => 5,
            'intelligence' => 5,
            'luck' => 5,
        ]);
        
        // Ensure stats are synced
        (new \App\Services\CharacterService())->syncStats($this->character);

        $this->enemy = Enemy::create([
            'name' => 'Test Enemy',
            'level' => 1,
            'strength' => 5,
            'constitution' => 5,
            'agility' => 5,
            'intelligence' => 5,
            'luck' => 5,
            'base_experience' => 100,
            'base_gold' => 10
        ]);
    }

    public function test_can_start_combat()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/combat/start', [
                'character_id' => $this->character->id,
                'enemy_ids' => [$this->enemy->id]
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'status',
                    'participants'
                ]
            ]);

        $this->assertDatabaseHas('combats', [
            'character_id' => $this->character->id,
            'status' => 'active'
        ]);
    }

    public function test_cannot_start_combat_if_already_in_combat()
    {
        // Start first combat
        (new \App\Services\CombatService(
            new \App\Services\EnemyService(), 
            new \App\Services\AbilityService()
        ))->startCombat($this->character, [$this->enemy->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/combat/start', [
                'character_id' => $this->character->id,
                'enemy_ids' => [$this->enemy->id]
            ]);

        $response->assertStatus(400);
    }

    public function test_can_attack()
    {
        // Start combat manually
        $combat = (new \App\Services\CombatService(
            new \App\Services\EnemyService(), 
            new \App\Services\AbilityService()
        ))->startCombat($this->character, [$this->enemy->id]);

        // Force player turn
        $combat->update(['current_turn' => 'player']);
        $participant = $combat->participants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/combat/{$combat->id}/attack", [
                'target_id' => $participant->id
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.action', 'attack');
    }

    public function test_cannot_attack_on_enemy_turn()
    {
        $combat = (new \App\Services\CombatService(
            new \App\Services\EnemyService(), 
            new \App\Services\AbilityService()
        ))->startCombat($this->character, [$this->enemy->id]);

        $combat->update(['current_turn' => 'enemies']);
        $participant = $combat->participants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/combat/{$combat->id}/attack", [
                'target_id' => $participant->id
            ]);

        $response->assertStatus(400);
    }
}
