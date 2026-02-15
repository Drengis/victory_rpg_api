<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use App\Services\CharacterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterRegenTest extends TestCase
{
    use RefreshDatabase;

    private CharacterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CharacterService();
        Carbon::setTestNow(Carbon::now()->startOfSecond());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_character_stats_synchronization_and_regen()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Test Hero',
            'class' => 'Воин',
            'strength' => 10,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        // 1. Проверка синхронизации
        $this->service->syncStats($character);
        
        $this->assertDatabaseHas('character_stats', [
            'character_id' => $character->id,
            'max_hp' => 100, // 10 constitution * 10
        ]);

        $this->assertDatabaseHas('character_dynamic_stats', [
            'character_id' => $character->id,
            'current_hp' => 100,
        ]);

        // 2. Проверка регенерации
        $dynamic = $character->dynamicStats;
        $dynamic->current_hp = 50; // Уменьшаем HP вручную
        // Устанавливаем время последнего обновления на 2 минуты назад
        $dynamic->last_regen_at = Carbon::now()->subMinutes(2)->startOfSecond();
        $dynamic->save();
        
        // Замораживаем время на текущем моменте (который на 2 минуты позже last_regen_at)
        // На самом деле он уже заморожен в setUp, но last_regen_at мы поставили в "прошлое" относительно этого момента.

        // HP Regen для воина с 10 constitution = 10 * 0.5 = 5 ед/мин.
        // За 2 минуты должно отрегениться 10 ед. Итого 50 + 10 = 60.
        
        // Используем fresh() чтобы сбросить кэш отношений и получить данные из БД
        $character = $character->fresh(['stats', 'dynamicStats']);
        $updatedDynamic = $this->service->refreshDynamicStats($character);

        $this->assertEqualsWithDelta(60, $updatedDynamic->current_hp, 0.001);
        $this->assertTrue($updatedDynamic->last_regen_at->isCurrentMinute());
    }

    public function test_no_regen_over_max()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Test Hero',
            'class' => 'Воин', // Используем Воина, чтобы max_hp был 100
            'strength' => 10,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        $this->service->syncStats($character);
        $dynamic = $character->dynamicStats;
        
        $dynamic->current_hp = 98;
        $dynamic->last_regen_at = Carbon::now()->subHours(1);
        $dynamic->save();

        $character = $character->fresh(['stats', 'dynamicStats']);
        $updatedDynamic = $this->service->refreshDynamicStats($character);

        $this->assertEqualsWithDelta(100, $updatedDynamic->current_hp, 0.001); // Не должно быть больше max_hp
    }

    public function test_no_regen_during_combat()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Combatant',
            'class' => 'Воин',
            'strength' => 10,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        $this->service->syncStats($character);
        $dynamic = $character->dynamicStats;
        $dynamic->current_hp = 50;
        $dynamic->is_in_combat = true;
        $dynamic->last_regen_at = Carbon::now()->subMinutes(10);
        $dynamic->save();

        $character = $character->fresh(['stats', 'dynamicStats']);
        $updatedDynamic = $this->service->refreshDynamicStats($character);

        // HP не должно измениться, так как персонаж в бою
        $this->assertEquals(50, $updatedDynamic->current_hp);
    }

    public function test_combat_round_regen()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Combatant',
            'class' => 'Воин',
            'strength' => 10,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        $this->service->syncStats($character);
        $dynamic = $character->dynamicStats;
        $dynamic->current_hp = 50;
        $dynamic->is_in_combat = true;
        $dynamic->save();

        // 10 constitution * 0.5 = 5 HP/min. 
        // 1 раунд = 1/10 минуты = 0.5 HP.
        
        $character = $character->fresh(['stats', 'dynamicStats']);
        $updatedDynamic = $this->service->applyCombatRoundRegen($character);

        $this->assertEqualsWithDelta(50.5, $updatedDynamic->current_hp, 0.001);
    }
}
