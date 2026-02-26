<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Character;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $currencyService;
    protected $character;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyService = new CurrencyService();
        
        $user = User::factory()->create();
        $this->character = Character::create([
            'user_id' => $user->id,
            'name' => 'RichHero',
            'class' => 'Воин',
            'gold' => 100
        ]);
    }

    public function test_can_add_gold()
    {
        $this->currencyService->addGold($this->character, 50);
        $this->assertEquals(150, $this->character->fresh()->gold);
    }

    public function test_can_subtract_gold()
    {
        $this->currencyService->subtractGold($this->character, 30);
        $this->assertEquals(70, $this->character->fresh()->gold);
    }

    public function test_cannot_subtract_more_than_owned()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Недостаточно золота.");
        
        $this->currencyService->subtractGold($this->character, 200);
    }

    public function test_cannot_add_negative_amount()
    {
        $this->expectException(\Exception::class);
        $this->currencyService->addGold($this->character, -10);
    }
}
