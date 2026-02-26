<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Character;
use App\Models\Item;
use App\Models\CharacterItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventorySaleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $character;
    protected $item;
    protected $charItem;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        
        $this->character = Character::create([
            'user_id' => $this->user->id,
            'name' => 'SellerHero',
            'class' => 'Воин',
            'gold' => 0
        ]);

        $this->item = Item::create([
            'name' => 'Loot Item',
            'type' => 'material',
            'quality' => 1,
            'base_price' => 100,
            'scaling_factor' => 0.1
        ]);

        $this->charItem = CharacterItem::create([
            'character_id' => $this->character->id,
            'item_id' => $this->item->id,
            'ilevel' => 1,
            'quantity' => 5
        ]);
    }

    public function test_can_sell_item_from_inventory()
    {
        // Базовая цена 100, iLvl 1 -> расчетная цена 100. Цена продажи 50% = 50.
        // Продаем 2 штуки -> 100 золота.
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/inventory/sell', [
                'character_item_id' => $this->charItem->id,
                'quantity' => 2
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.gold_received', 100)
            ->assertJsonPath('data.current_gold', 100);

        $this->assertDatabaseHas('character_items', [
            'id' => $this->charItem->id,
            'quantity' => 3
        ]);

        $this->assertDatabaseHas('characters', [
            'id' => $this->character->id,
            'gold' => 100
        ]);
    }

    public function test_item_deleted_when_sold_out()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/inventory/sell', [
                'character_item_id' => $this->charItem->id,
                'quantity' => 5
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('character_items', ['id' => $this->charItem->id]);
    }

    public function test_cannot_sell_other_players_item()
    {
        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('other')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->postJson('/api/inventory/sell', [
                'character_item_id' => $this->charItem->id,
                'quantity' => 1
            ]);

        $response->assertStatus(403);
    }
}
