<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\Item;
use App\Models\User;
use App\Services\CharacterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    private CharacterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CharacterService();
    }

    public function test_item_rarity_bonus_calculation()
    {
        // База 10 силы, Редкое (Blue) x1.5
        $item = Item::create([
            'name' => 'Blue Sword',
            'type' => 'weapon',
            'quality' => Item::QUALITY_RARE,
            'strength' => 10,
        ]);

        $this->assertEquals(15, $item->getBonus('strength'));
    }

    public function test_equipping_item_recalculates_stats()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Warrior',
            'class' => 'Воин',
            'strength' => 10, // База Воина +10% -> 11
        ]);

        $this->service->syncStats($character);
        
        // До надевания: Strength = 11. Physical Damage Bonus = 11 * 2 = 22%
        $this->assertEquals(22, $character->stats->physical_damage_bonus);

        // Создаем предмет: Редкие перчатки на +10 Силы (итого +15 силы)
        $item = Item::create([
            'name' => 'Rare Gauntlets',
            'type' => 'gloves',
            'quality' => Item::QUALITY_RARE,
            'strength' => 10,
        ]);

        $charItem = CharacterItem::create([
            'character_id' => $character->id,
            'item_id' => $item->id,
        ]);

        // Надеваем
        $this->service->equipItem($character, $charItem, 'gloves');

        $character->refresh();
        // Новая формула: (Base 10 + Gear 15) * 1.1 = 25 * 1.1 = 27.5 -> round(28)?
        // PHP round(27.5) = 28.0 (по умолчанию round_half_up в новых PHP)
        
        // Physical Damage Bonus = 28 * 2 = 56%
        $this->assertEquals(56, $character->stats->physical_damage_bonus);
    }

    public function test_weapon_damage_calculation()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Archer',
            'class' => 'Лучник',
            'strength' => 5,
            'agility' => 10,
        ]);

        // Лучник: Str -10%, Agi +10%.
        // Str: 5 * 0.9 = 4.5 -> 5
        // Agi: 10 * 1.1 = 11
        // Accuracy: 11 * 4 = 44% (база 2 + лучник 2)
        // Damage Bonus: Str(1%) + Agi(1%) = 5 * 1 + 11 * 1 = 16%
        
        $this->service->syncStats($character);
        $this->assertEquals(16, $character->stats->physical_damage_bonus);

        // Зеленый лук (Uncommon x1.2): 10-20 урона
        $weapon = Item::create([
            'name' => 'Green Bow',
            'type' => 'weapon',
            'quality' => Item::QUALITY_UNCOMMON,
            'min_damage' => 10,
            'max_damage' => 20,
        ]);

        $charItem = CharacterItem::create([
            'character_id' => $character->id,
            'item_id' => $weapon->id,
        ]);

        $this->service->equipItem($character, $charItem, 'weapon');

        // Базовый урон оружия с качеством: 10*1.2=12, 20*1.2=24
        // Финальный урон: 12 * (1 + 16/100) = 12 * 1.16 = 13.92 -> 14
        // 24 * 1.16 = 27.84 -> 28
        
        $character->refresh();
        $this->assertEquals(14, $character->stats->min_damage);
        $this->assertEquals(28, $character->stats->max_damage);
    }

    public function test_ilevel_scaling_bonus()
    {
        // База 10 силы, iLvl 11 (рост +10% за уровень, итого +100% за 10 уровней сверх первого)
        // scaling_factor по дефолту 0.1
        $item = Item::create([
            'name' => 'High Level Sword',
            'type' => 'weapon',
            'quality' => Item::QUALITY_COMMON, // x1.0
            'strength' => 10,
            'scaling_factor' => 0.1,
        ]);

        // iLvl 11: Base 10 * (1 + (11-1)*0.1) = 10 * 2 = 20
        $this->assertEquals(20, $item->getBonus('strength', 11));

        // iLvl 11 + Rare (x1.5): 20 * 1.5 = 30
        $item->quality = Item::QUALITY_RARE;
        $this->assertEquals(30, $item->getBonus('strength', 11));
    }
}
