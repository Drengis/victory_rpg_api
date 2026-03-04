<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use App\Services\CharacterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterStatsTest extends TestCase
{
    use RefreshDatabase;

    private CharacterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CharacterService();
    }

    public function test_warrior_stats_calculation()
    {
        $user = User::factory()->create();
        $character = new Character([
            'user_id' => $user->id,
            'class' => 'Воин',
            'strength' => 20,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        $stats = $this->service->calculateFinalStats($character);

        // Воин: +10% Сила (22), -10% Интеллект (9)
        // Accuracy: Agility(10)*2 + Strength(22)*0.5 = 20 + 11 = 31
        $this->assertEquals(31, $stats['accuracy']);
        
        // Physical Damage: Str(22) -> 22%(univ) + 22%(class) = 44%
        $this->assertEquals(44, $stats['physical_damage_bonus']);
        
        // Magical Damage: Int(9) -> 9%(univ)
        $this->assertEquals(9, $stats['magical_damage_bonus']);
    }

    public function test_mage_stats_calculation()
    {
        $user = User::factory()->create();
        $character = new Character([
            'user_id' => $user->id,
            'class' => 'Маг',
            'strength' => 10,
            'agility' => 10,
            'constitution' => 20,
            'intelligence' => 20,
            'luck' => 10,
        ]);

        $stats = $this->service->calculateFinalStats($character);

        // Маг: +10% Интеллект (22), -10% Телосложение (18)
        // Mana: Intel(22) * 15 = 330
        $this->assertEquals(330, $stats['max_mp']);
        
        // Accuracy: Agility(10)*2 + Intel(22)*0.5 = 20 + 11 = 31
        $this->assertEquals(31, $stats['accuracy']);
        
        // Magical Damage: Int(22) -> 22%(univ) + 22%(class) = 44%
        $this->assertEquals(44, $stats['magical_damage_bonus']);
        
        // Physical Damage: Str(10) -> 10%(univ)
        $this->assertEquals(10, $stats['physical_damage_bonus']);
    }

    public function test_passive_skills_application()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Test Hero',
            'class' => 'Воин',
            'strength' => 10,
            'agility' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        // Создаем пассивный навык
        $passive = ClassAbility::create([
            'class' => 'воин',
            'ability_name' => 'Тестовая Крепость',
            'ability_type' => 'passive',
            'mp_cost' => 0,
            'gold_cost' => 0,
            'level_required' => 1,
            'effect_type' => 'stat_boost',
            'effect_formula' => 'armor * 0.1',
            'description' => 'Test',
            'cooldown_turns' => 0,
            'duration' => 0
        ]);

        // Привязываем навык к персонажу
        $character->abilities()->attach($passive->id);

        // Для теста брони нам нужно какое-то базовое значение брони
        // В CharacterService броня берется из gearStats
        // Эмулируем наличие брони через расчет (в реальности она из эквипа)
        
        $stats = $this->service->calculateFinalStats($character);
        
        // По умолчанию броня 0, 0 + 10% = 0.
        // Проверим на HP, так как оно всегда > 0
        $passiveHp = ClassAbility::create([
            'class' => 'воин',
            'ability_name' => 'Тестовая Закалка',
            'ability_type' => 'passive',
            'mp_cost' => 0,
            'gold_cost' => 0,
            'level_required' => 1,
            'effect_type' => 'stat_boost',
            'effect_formula' => 'max_hp * 0.15',
            'description' => 'Test',
            'cooldown_turns' => 0,
            'duration' => 0
        ]);
        $character->abilities()->attach($passiveHp->id);

        $stats = $this->service->calculateFinalStats($character);

        // Базовое HP для Воина с 10 Constitution: 10 * 10 = 100.
        // С пассивкой: 100 + 15% = 115.
        $this->assertEquals(115, $stats['max_hp']);
    }
}
