<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClassAbility;

class ClassAbilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Воин: Блок
        ClassAbility::updateOrCreate(
            ['class' => 'воин', 'ability_name' => 'Блок'],
            [
                'ability_type' => 'defense',
                'mp_cost' => 15,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 1,
                'effect_type' => 'temp_armor',
                'effect_formula' => 'constitution',
                'description' => 'Воин принимает защитную стойку, увеличивая броню на значение Выносливости на следующий ход.',
            ]
        );

        // Лучник: Уклонение
        ClassAbility::updateOrCreate(
            ['class' => 'лучник', 'ability_name' => 'Уклонение'],
            [
                'ability_type' => 'defense',
                'mp_cost' => 15,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 1,
                'effect_type' => 'temp_evasion',
                'effect_formula' => 'agility * 0.5',
                'description' => 'Лучник готовится к уклонению, увеличивая шанс уклонения на 50% от Ловкости на следующий ход.',
            ]
        );

        // Маг: Магический Барьер
        ClassAbility::updateOrCreate(
            ['class' => 'маг', 'ability_name' => 'Магический Барьер'],
            [
                'ability_type' => 'defense',
                'mp_cost' => 35,
                'max_uses_per_combat' => 1,
                'cooldown_turns' => 0,
                'effect_type' => 'barrier',
                'effect_formula' => 'intelligence * 1.5',
                'description' => 'Маг создаёт магический щит, поглощающий урон. Стоимость: 30 маны. Можно использовать 1 раз за бой.',
            ]
        );
    }
}
