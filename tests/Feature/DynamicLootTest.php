<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Enemy;
use App\Models\Item;
use App\Models\User;
use App\Services\CharacterService;
use App\Services\EnemyService;
use App\Services\LootService;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicLootTest extends TestCase
{
    use RefreshDatabase;

    private RewardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(RewardService::class);

        // Создаем шаблоны для всех типов, чтобы ролл не промахивался мимо созданных вещей
        $types = ['weapon', 'head', 'chest', 'hands', 'legs', 'feet', 'belt', 'neck', 'ring', 'trinket'];
        foreach ($types as $type) {
            Item::create(['name' => "Common $type", 'type' => $type, 'quality' => 1, 'base_price' => 10]);
            Item::create(['name' => "Green $type", 'type' => $type, 'quality' => 2, 'base_price' => 30]);
        }
    }

    public function test_low_level_enemy_drops_only_common_gear()
    {
        $user = User::factory()->create();
        $character = Character::create(['user_id' => $user->id, 'name' => 'Noob', 'class' => 'warrior']);
        app(\App\Services\CharacterService::class)->syncStats($character);
        
        // Крыса 1 уровня
        $enemy = Enemy::create([
            'name' => 'Lvl 1 Rat', 'level' => 1, 'strength' => 5, 'agility' => 5, 
            'constitution' => 5, 'intelligence' => 5, 'luck' => 5
        ]);

        // Делаем 200 убийств (было 100)
        $droppedGreen = false;
        for ($i = 0; $i < 200; $i++) {
            $reward = $this->service->rewardCharacter($character, $enemy);
            if (isset($reward['dynamic_gear'])) {
                $item = Item::find($reward['dynamic_gear']['item_id']);
                if ($item->quality > 1) $droppedGreen = true;
            }
        }

        $this->assertFalse($droppedGreen, 'Green item dropped from lvl 1 enemy!');
    }

    public function test_high_level_enemy_can_drop_uncommon_gear()
    {
        // Создаем моба 15 уровня (шанс 20% на зелень при ролле шмотки)
        $enemy = Enemy::create([
            'name' => 'Lvl 15 Elite', 'level' => 15, 'strength' => 5, 'agility' => 5, 
            'constitution' => 5, 'intelligence' => 5, 'luck' => 5
        ]);

        $user = User::factory()->create();
        $character = Character::create(['user_id' => $user->id, 'name' => 'Pro', 'class' => 'warrior']);
        app(\App\Services\CharacterService::class)->syncStats($character);

        $droppedGreen = false;
        for ($i = 0; $i < 500; $i++) {
            $reward = $this->service->rewardCharacter($character, $enemy);
            if (isset($reward['dynamic_gear'])) {
                $item = Item::find($reward['dynamic_gear']['item_id']);
                if ($item->quality == 2) {
                    $droppedGreen = true;
                    break;
                }
            }
        }

        $this->assertTrue($droppedGreen, 'Green item never dropped from lvl 15 enemy in 500 tries');
    }

    public function test_ilevel_is_randomized_around_enemy_level()
    {
        $enemy = Enemy::create([
            'name' => 'Lvl 10 Mob', 'level' => 10, 'strength' => 5, 'agility' => 5, 
            'constitution' => 5, 'intelligence' => 5, 'luck' => 5
        ]);

        $user = User::factory()->create();
        $character = Character::create(['user_id' => $user->id, 'name' => 'LvlTester', 'class' => 'warrior']);
        app(\App\Services\CharacterService::class)->syncStats($character);

        $foundVaryingIlevel = false;
        $baseIlevel = 10;

        // 500 итераций, чтобы точно выбить несколько вещей и увидеть разницу в iLvl
        for ($i = 0; $i < 500; $i++) {
            $reward = $this->service->rewardCharacter($character, $enemy);
            if (isset($reward['dynamic_gear'])) {
                $ilevel = $reward['dynamic_gear']['ilevel'];
                $this->assertGreaterThanOrEqual(8, $ilevel);
                $this->assertLessThanOrEqual(12, $ilevel);
                
                if ($ilevel != $baseIlevel) {
                    $foundVaryingIlevel = true;
                }
            }
        }
        
        $this->assertTrue($foundVaryingIlevel, 'iLvl was always exactly the same as enemy level');
    }
}
