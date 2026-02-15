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
        
        $charService = new CharacterService();
        $enemyService = new EnemyService();
        $lootService = new LootService();
        
        $this->service = new RewardService($charService, $enemyService, $lootService);
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

        // Ожидаем 20 * 2 = 40 XP и 50 * 2 = 100 Gold
        $character->refresh();
        $this->assertEquals(40, $character->experience);
        $this->assertEquals(100, $character->gold);
    }

    public function test_loot_generation_and_delivery()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'LootTester',
            'class' => 'mage',
        ]);

        // 1. Создаем таблицу лута
        $lootTable = LootTable::create(['name' => 'Rat Loot']);
        
        // 2. Создаем предмет
        $item = Item::create([
            'name' => 'Rat Tail',
            'rarity' => 'common',
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

        // 4. Создаем моба с этой таблицей
        $enemy = Enemy::create([
            'name' => 'Rat',
            'loot_table_id' => $lootTable->id,
            'strength' => 5, 'agility' => 5, 'constitution' => 5, 'intelligence' => 5, 'luck' => 5,
        ]);

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
