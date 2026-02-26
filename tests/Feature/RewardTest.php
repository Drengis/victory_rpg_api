<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Enemy;
use App\Models\Item;
use App\Models\LootItem;
use App\Models\LootTable;
use App\Models\User;
use App\Services\CharacterService;
use App\Services\EnemyService;
use App\Services\LootService;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardTest extends TestCase
{
    use RefreshDatabase;

    private RewardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(RewardService::class);
    }

    public function test_character_receives_scaled_rewards()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Tester',
            'class' => 'warrior',
            'level' => 1,
            'experience' => 0,
            'gold' => 0,
        ]);
        app(\App\Services\CharacterService::class)->syncStats($character);

        // Создаем моба 11 уровня (+100% наград при факторе 0.1)
        $enemy = Enemy::create([
            'name' => 'Rich Rat',
            'level' => 11,
            'base_experience' => 20,
            'base_gold' => 50,
            'scaling_factor' => 0.1,
            'strength' => 5,
            'agility' => 5,
            'constitution' => 5,
            'intelligence' => 5,
            'luck' => 5,
        ]);

        $this->service->rewardCharacter($character, $enemy);

        // Ожидаем 40 * 1.025 = 41 XP и 100 * 1.025 = 102.5 -> 103 Gold
        $character->refresh();
        $this->assertEquals(41, $character->experience);
        $this->assertEquals(103, $character->gold);
    }

    public function test_loot_generation_and_delivery()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'LootTester',
            'class' => 'mage',
        ]);
        app(\App\Services\CharacterService::class)->syncStats($character);
        app(\App\Services\CharacterService::class)->syncStats($character);

        // 1. Создаем таблицу лута
        $lootTable = LootTable::create(['name' => 'Rat Loot']);
        
        // 2. Создаем предмет
        $item = Item::create([
            'name' => 'Rat Tail',
            'quality' => 1,
            'type' => 'material',
        ]);

        // 3. Добавляем предмет в таблицу со 100% шансом
        LootItem::create([
            'loot_table_id' => $lootTable->id,
            'item_id' => $item->id,
            'chance' => 100.0,
            'min_quantity' => 2,
            'max_quantity' => 2,
        ]);

        // 4. Создаем моба
        $enemy = Enemy::create([
            'name' => 'Rat',
            'strength' => 5, 'agility' => 5, 'constitution' => 5, 'intelligence' => 5, 'luck' => 5,
        ]);
        $enemy->lootTables()->attach($lootTable->id);

        $reward = $this->service->rewardCharacter($character, $enemy);

        // Проверяем результат
        $this->assertCount(1, $reward['loot']);
        $this->assertEquals(2, $reward['loot'][$item->id]);

        // Проверяем инвентарь персонажа
        $this->assertEquals(1, $character->items()->count());
        $this->assertEquals($item->id, $character->items()->first()->item_id);
        $this->assertEquals(2, $character->items()->first()->quantity);
    }
}
