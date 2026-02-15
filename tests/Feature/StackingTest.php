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

class StackingTest extends TestCase
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

    public function test_materials_are_stacked_in_inventory()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Stacker',
            'class' => 'warrior',
        ]);

        $item = Item::create([
            'name' => 'Rat Tail',
            'type' => 'material',
            'quality' => 1,
        ]);

        $lootTable = LootTable::create(['name' => 'Rat']);
        LootItem::create([
            'loot_table_id' => $lootTable->id,
            'item_id' => $item->id,
            'chance' => 100.0,
            'min_quantity' => 1,
            'max_quantity' => 1,
        ]);

        $enemy = Enemy::create([
            'name' => 'Rat',
            'loot_table_id' => $lootTable->id,
            'strength' => 5, 'agility' => 5, 'constitution' => 5, 'intelligence' => 5, 'luck' => 5,
        ]);

        // Убиваем крысу дважды
        $this->service->rewardCharacter($character, $enemy);
        $this->service->rewardCharacter($character, $enemy);

        // Должна быть 1 запись с количеством 2
        $this->assertEquals(1, $character->items()->count());
        $this->assertEquals(2, $character->items()->first()->quantity);
    }

    public function test_equipment_is_not_stacked()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'GearTester',
            'class' => 'warrior',
        ]);

        $item = Item::create([
            'name' => 'Rusty Sword',
            'type' => 'weapon',
            'quality' => 1,
        ]);

        $lootTable = LootTable::create(['name' => 'Boss']);
        LootItem::create([
            'loot_table_id' => $lootTable->id,
            'item_id' => $item->id,
            'chance' => 100.0,
            'min_quantity' => 1,
            'max_quantity' => 1,
        ]);

        $enemy = Enemy::create([
            'name' => 'Boss',
            'loot_table_id' => $lootTable->id,
            'strength' => 5, 'agility' => 5, 'constitution' => 5, 'intelligence' => 5, 'luck' => 5,
        ]);

        // Убиваем босса дважды
        $this->service->rewardCharacter($character, $enemy);
        $this->service->rewardCharacter($character, $enemy);

        // Должно быть 2 отдельные записи
        $this->assertEquals(2, $character->items()->count());
        $this->assertEquals(1, $character->items()->first()->quantity);
        $this->assertEquals(1, $character->items()->latest()->first()->quantity);
    }
}
