<?php

namespace Tests\Feature;

use App\Models\Item;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EconomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_common_item_base_price()
    {
        $item = Item::create([
            'name' => 'Rusty Sword',
            'type' => 'weapon',
            'quality' => Item::QUALITY_COMMON,
            'base_price' => 20,
        ]);

        // Уровень 1: 20 * 1.0 (multiplier) * 1.0 (scale) = 20
        $this->assertEquals(20, $item->calculatePrice(1));
        $this->assertTrue($item->isPurchasable());
    }

    public function test_uncommon_item_multiplier()
    {
        $item = Item::create([
            'name' => 'Sharp Dagger',
            'type' => 'weapon',
            'quality' => Item::QUALITY_UNCOMMON,
            'base_price' => 20,
        ]);

        // Уровень 1: 20 * 3.0 (multiplier) = 60
        $this->assertEquals(60, $item->calculatePrice(1));
        $this->assertTrue($item->isPurchasable());
    }

    public function test_price_scaling_with_ilevel()
    {
        $item = Item::create([
            'name' => 'Veteran Sword',
            'type' => 'weapon',
            'quality' => Item::QUALITY_COMMON,
            'base_price' => 100,
        ]);

        // Уровень 2: 100 * (1 + (2-1)*0.2) = 120
        $this->assertEquals(120, $item->calculatePrice(2));
        
        // Уровень 6: 100 * (1 + 1.0) = 200
        $this->assertEquals(200, $item->calculatePrice(6));
    }

    public function test_rare_items_not_purchasable()
    {
        $item = Item::create([
            'name' => 'Dragon Slayer',
            'type' => 'weapon',
            'quality' => Item::QUALITY_RARE,
            'base_price' => 100,
        ]);

        $this->assertFalse($item->isPurchasable());
        // Цена Rare (x10): 100 * 10 = 1000
        $this->assertEquals(1000, $item->calculatePrice(1));
    }
}
