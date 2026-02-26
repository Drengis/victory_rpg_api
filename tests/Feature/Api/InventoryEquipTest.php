<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Character;
use App\Models\Item;
use App\Models\CharacterItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryEquipTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $character;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        
        $this->character = Character::create([
            'user_id' => $this->user->id,
            'name' => 'EquipHero',
            'class' => 'воин',
            'gold' => 0
        ]);
    }

    public function test_can_equip_item_to_legs_slot()
    {
        $item = Item::create([
            'name' => 'Test Legs',
            'type' => 'legs',
            'quality' => 1,
            'base_price' => 10,
            'scaling_factor' => 0.1
        ]);

        $charItem = CharacterItem::create([
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'ilevel' => 1,
            'is_equipped' => false
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/inventory/equip', [
                'character_item_id' => $charItem->id,
                'slot' => 'legs'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('character_items', [
            'id' => $charItem->id,
            'slot' => 'legs',
            'is_equipped' => true
        ]);
    }

    public function test_can_equip_item_to_head_slot()
    {
        $item = Item::create([
            'name' => 'Test Helmet',
            'type' => 'head',
            'quality' => 1,
            'base_price' => 10,
            'scaling_factor' => 0.1
        ]);

        $charItem = CharacterItem::create([
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'ilevel' => 1,
            'is_equipped' => false
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/inventory/equip', [
                'character_item_id' => $charItem->id,
                'slot' => 'head'
            ]);

        $response->assertStatus(200);
    }
}
