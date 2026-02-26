<?php

namespace Tests\Feature\Api;

use App\Models\Character;
use App\Models\Item;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Character $character;
    protected Shop $shop;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->character = Character::create([
            'user_id' => $this->user->id,
            'name' => 'Hero',
            'class' => 'Воин',
            'gold' => 1000,
        ]);
        
        // Создаем статы и динамические статы
        \App\Models\CharacterStat::create(['character_id' => $this->character->id]);
        \App\Models\CharacterDynamicStat::create(['character_id' => $this->character->id]);
        
        $this->shop = Shop::create([
            'name' => 'Test Shop',
            'description' => 'A shop for testing'
        ]);
        
        $this->item = Item::create([
            'name' => 'Test Swrod',
            'type' => 'weapon',
            'base_price' => 100,
            'quality' => 1,
            'stats_json' => '{"min_damage": 5, "max_damage": 10}'
        ]);
        
        $this->shop->items()->attach($this->item->id, ['ilevel' => 1]);
    }

    public function test_can_list_shops()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/shops');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_view_shop_details()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/shops/{$this->shop->id}");

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Test Shop')
            ->assertJsonCount(1, 'items');
    }

    public function test_can_buy_item()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/shops/{$this->shop->id}/buy", [
                'character_id' => $this->character->id,
                'item_id' => $this->item->id,
                'quantity' => 1
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('item_name', 'Test Swrod')
            ->assertJsonPath('current_gold', 900);

        $this->assertDatabaseHas('character_items', [
            'character_id' => $this->character->id,
            'item_id' => $this->item->id,
        ]);

        $this->assertEquals(900, $this->character->fresh()->gold);
    }

    public function test_cannot_buy_with_insufficient_gold()
    {
        $this->character->update(['gold' => 50]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/shops/{$this->shop->id}/buy", [
                'character_id' => $this->character->id,
                'item_id' => $this->item->id,
                'quantity' => 1
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Недостаточно золота для покупки.');
    }

    public function test_cannot_buy_item_not_in_shop()
    {
        $otherItem = Item::create([
            'name' => 'Other Item',
            'type' => 'material',
            'base_price' => 50,
            'quality' => 1
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/shops/{$this->shop->id}/buy", [
                'character_id' => $this->character->id,
                'item_id' => $otherItem->id,
                'quantity' => 1
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Этот товар не продается в данном магазине.');
    }
}
