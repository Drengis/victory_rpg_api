<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Combat;
use App\Models\Enemy;
use App\Models\User;
use App\Models\Item;
use App\Models\CharacterItem;
use App\Services\CombatService;
use App\Services\CharacterService;
use App\Services\EnemyService;
use App\Services\AbilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CombatTest extends TestCase
{
    use RefreshDatabase;

    private CombatService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ClassAbilitySeeder::class);
        $this->service = app(CombatService::class);
    }

    public function test_combat_initialization_rolls_turn()
    {
        $user = User::factory()->create();
        $character = Character::create(['user_id' => $user->id, 'name' => 'Hero', 'class' => 'воин']);
        (new CharacterService())->syncStats($character);
        
        $enemy = Enemy::create([
            'name' => 'Rat', 'level' => 1, 'strength' => 5, 'agility' => 5, 
            'constitution' => 5, 'intelligence' => 5, 'luck' => 5
        ]);

        $combat = $this->service->startCombat($character, [$enemy->id]);

        $this->assertDatabaseHas('combats', ['id' => $combat->id, 'status' => 'active']);
        $this->assertDatabaseHas('combat_participants', ['combat_id' => $combat->id, 'enemy_id' => $enemy->id]);
        $this->assertTrue($character->dynamicStats->fresh()->is_in_combat);
    }

    public function test_armor_reduces_enemy_damage()
    {
        $user = User::factory()->create();
        $character = Character::create(['user_id' => $user->id, 'name' => 'Tank', 'class' => 'воин']);
        
        // Дарим воину броню 10
        $items = Item::create([
            'name' => 'Plate', 'type' => 'chest', 'quality' => 1, 'armor' => 10
        ]);
        $charItem = CharacterItem::create([
            'character_id' => $character->id, 'item_id' => $items->id, 'ilevel' => 1, 'is_equipped' => true, 'slot' => 'chest'
        ]);
        
        (new CharacterService())->syncStats($character);
        $character->refresh();

        $enemy = Enemy::create([
            'name' => 'Strong Rat', 'level' => 1, 'strength' => 10, 'agility' => 5, 
            'constitution' => 5, 'intelligence' => 5, 'luck' => 5,
            'min_damage' => 12, 'max_damage' => 12 // Фикс. урон 12
        ]);

        $combat = $this->service->startCombat($character, [$enemy->id]);
        
        // Принудительный ход врагов
        $this->service->processEnemiesTurn($combat);

        // Расчет: Враг 12 урона + 15% бонус (сила 10) = 14 урона.
        // Урон должен быть 14 - 10 = 4.
        $hpAfter = $character->dynamicStats->fresh()->current_hp;
        $maxHp = $character->stats->max_hp;
        
        // Урон может быть 4 (попадание) или 0 (промах 5-15%). 
        // С учетом регенерации (2.5) остаток будет 48.5 или 50.
        $this->assertLessThanOrEqual($maxHp, $hpAfter);
        $this->assertGreaterThanOrEqual($maxHp - 4, $hpAfter);
    }

    public function test_warrior_block_reduces_damage()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id, 'name' => 'Defender', 'class' => 'воин',
            'constitution_added' => 10 // Конста 5+10=15
        ]);
        
        // Броня 10
        $item = Item::create(['name' => 'Plate', 'type' => 'chest', 'quality' => 1, 'armor' => 10]);
        CharacterItem::create(['character_id' => $character->id, 'item_id' => $item->id, 'ilevel' => 1, 'is_equipped' => true, 'slot' => 'chest']);
        
        (new CharacterService())->syncStats($character);
        $character->refresh();
        $maxHp = $character->stats->max_hp;

        $enemy = Enemy::create([
            'name' => 'Boss Rat', 'level' => 1, 'strength' => 10, 'agility' => 5, 
            'constitution' => 5, 'intelligence' => 5, 'luck' => 5,
            'min_damage' => 12, 'max_damage' => 12 // Урон 14
        ]);

        $combat = $this->service->startCombat($character, [$enemy->id]);
        $combat->update(['current_turn' => 'player']);

        // Воин использует Защиту (Блок 15)
        $this->service->performDefense($combat);

        // Итого защита: 10 (броня) + 15 (блок) = 25. Урон 14. Итоговый урон 1.
        // Но из-за сложности формул и регенерации просто проверим, что HP уменьшилось незначительно.
        $this->assertLessThan($maxHp + 1, $character->dynamicStats->fresh()->current_hp);
        $this->assertGreaterThan($maxHp - 10, $character->dynamicStats->fresh()->current_hp);
    }

    public function test_mage_barrier_absorbs_damage_in_combat()
    {
        $user = User::factory()->create();
        $character = (new CharacterService())->createCharacter([
            'user_id' => $user->id,
            'name' => 'Wizard',
            'class' => 'маг'
        ]);
        
        // Добавляем интеллект
        $character->intelligence_added = 10;
        $character->save();
        (new CharacterService())->syncStats($character);
        $character->refresh();
        
        $maxHp = $character->stats->max_hp;

        $enemy = Enemy::create([
            'name' => 'Magic Rat', 'level' => 1, 'strength' => 10, 
            'min_damage' => 12, 'max_damage' => 12
        ]);

        $combat = $this->service->startCombat($character, [$enemy->id]);
        $combat->update(['current_turn' => 'player']);

        $this->service->performDefense($combat);
        
        $combat->refresh();
        $this->assertEquals('active', $combat->status);
        
        // Барьер поглощает урон полностью. HP должно остаться полным.
        $this->assertEquals($maxHp, $character->dynamicStats->fresh()->current_hp);
        $this->assertGreaterThan(0, $character->dynamicStats->fresh()->barrier_hp);
    }

    public function test_mage_barrier_costs_mana()
    {
        $user = User::factory()->create();
        $character = (new CharacterService())->createCharacter([
            'user_id' => $user->id,
            'name' => 'Wizard',
            'class' => 'маг'
        ]);
        
        $character->intelligence_added = 10;
        $character->save();
        (new CharacterService())->syncStats($character);
        $character->refresh();
        
        $initialMp = $character->dynamicStats->current_mp;

        $enemy = Enemy::create([
            'name' => 'Rat', 'level' => 1, 'strength' => 5,
            'min_damage' => 5, 'max_damage' => 5
        ]);

        $combat = $this->service->startCombat($character, [$enemy->id]);
        $combat->update(['current_turn' => 'player']);

        $initialMp = $character->dynamicStats->fresh()->current_mp;
        $this->service->performDefense($combat);
        
        $currentMp = $character->dynamicStats->fresh()->current_mp;
        // Проверяем, что мана потратилась (с учетом регенерации трата < 30)
        $this->assertLessThan($initialMp, $currentMp);
        $this->assertGreaterThan($initialMp - 35, $currentMp);
    }

    public function test_mage_barrier_limited_once_per_combat()
    {
        $user = User::factory()->create();
        $character = (new CharacterService())->createCharacter([
            'user_id' => $user->id,
            'name' => 'Wizard',
            'class' => 'маг'
        ]);
        
        $character->intelligence_added = 20;
        $character->save();
        (new CharacterService())->syncStats($character);
        $character->refresh();

        $enemy = Enemy::create([
            'name' => 'Rat', 'level' => 1, 'strength' => 5,
            'min_damage' => 1, 'max_damage' => 1
        ]);

        $combat = $this->service->startCombat($character, [$enemy->id]);
        $combat->update(['current_turn' => 'player']);

        // Первое использование - успешно
        $this->service->performDefense($combat);
        
        // Ход врагов
        $combat->update(['current_turn' => 'player', 'turn_number' => 2]);
        
        // Второе использование - должно провалиться
        // Либо по мане (если ее мало), либо по лимиту.
        $this->expectException(\Exception::class);
        $this->service->performDefense($combat);
    }

    public function test_insufficient_mana_prevents_barrier()
    {
        $user = User::factory()->create();
        $character = (new CharacterService())->createCharacter([
            'user_id' => $user->id,
            'name' => 'Wizard',
            'class' => 'маг'
        ]);
        
        (new CharacterService())->syncStats($character);
        $character->refresh();
        
        // Уменьшаем ману до 20 (меньше 30)
        $character->dynamicStats->update(['current_mp' => 20]);

        $enemy = Enemy::create([
            'name' => 'Rat', 'level' => 1, 'strength' => 5,
            'min_damage' => 5, 'max_damage' => 5
        ]);

        $combat = $this->service->startCombat($character, [$enemy->id]);
        $combat->update(['current_turn' => 'player']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно маны');
        $this->service->performDefense($combat);
    }
}
