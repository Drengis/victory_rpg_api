<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\Item;
use App\Models\User;
use App\Services\CharacterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private CharacterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CharacterService();
    }

    public function test_warrior_cannot_equip_mage_staff()
    {
        $user = User::factory()->create();
        $warrior = Character::create(['user_id' => $user->id, 'name' => 'Grom', 'class' => 'воин']);
        
        $staff = Item::create([
            'name' => 'Mage Staff', 
            'type' => 'weapon', 
            'quality' => 1, 
            'required_class' => 'маг'
        ]);

        $charItem = CharacterItem::create([
            'character_id' => $warrior->id,
            'item_id' => $staff->id,
            'ilevel' => 1,
            'quantity' => 1
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Этот предмет предназначен для класса: маг.");

        $this->service->equipItem($warrior, $charItem, 'weapon');
    }

    public function test_any_class_can_equip_common_items()
    {
        $user = User::factory()->create();
        $warrior = Character::create(['user_id' => $user->id, 'name' => 'Grom', 'class' => 'воин']);
        
        $ring = Item::create([
            'name' => 'Copper Ring', 
            'type' => 'ring', 
            'quality' => 1, 
            'required_class' => null // Общий предмет
        ]);

        $charItem = CharacterItem::create([
            'character_id' => $warrior->id,
            'item_id' => $ring->id,
            'ilevel' => 1,
            'quantity' => 1
        ]);

        // Не должно выкидывать исключение
        $this->service->equipItem($warrior, $charItem, 'ring1');
        
        $this->assertTrue($charItem->fresh()->is_equipped);
    }
}
