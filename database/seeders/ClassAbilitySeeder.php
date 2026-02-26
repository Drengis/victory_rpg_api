<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassAbility;

class ClassAbilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $abilities = [
            // Воин
            [
                'class' => 'воин',
                'ability_name' => 'Защитная стойка',
                'ability_type' => 'defense',
                'mp_cost' => 10,
                'gold_cost' => 15,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 2,
                'level_required' => 1,
                'effect_type' => 'temp_armor',
                'effect_formula' => 'constitution',
                'description' => 'Воин встает в глухую оборону, увеличивая броню на значение Стойкости на 2 хода.',
            ],
            [
                'class' => 'воин',
                'ability_name' => 'Мощный удар',
                'ability_type' => 'attack',
                'mp_cost' => 18,
                'gold_cost' => 35,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 1,
                'duration' => 1,
                'level_required' => 3,
                'effect_type' => 'deal_damage',
                'effect_formula' => 'strength * 1.5',
                'description' => 'Сокрушительный удар, наносящий 150% урона от силы.',
            ],
            // Лучник
            [
                'class' => 'лучник',
                'ability_name' => 'Уклонение',
                'ability_type' => 'defense',
                'mp_cost' => 8,
                'gold_cost' => 15,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 2,
                'level_required' => 1,
                'effect_type' => 'temp_evasion',
                'effect_formula' => 'agility * 0.5',
                'description' => 'Лучник становится крайне подвижным, увеличивая уклонение на 50% от Ловкости на 2 хода.',
            ],
            [
                'class' => 'лучник',
                'ability_name' => 'Точный выстрел',
                'ability_type' => 'attack',
                'mp_cost' => 15,
                'gold_cost' => 35,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 2,
                'duration' => 1,
                'level_required' => 5,
                'effect_type' => 'deal_damage',
                'effect_formula' => 'agility * 2.0',
                'description' => 'Выстрел из засады, наносящий 200% урона от Ловкости.',
            ],
            // Маг
            [
                'class' => 'маг',
                'ability_name' => 'Магический щит',
                'ability_type' => 'defense',
                'mp_cost' => 15,
                'gold_cost' => 15,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 1,
                'level_required' => 1,
                'effect_type' => 'barrier',
                'effect_formula' => 'intelligence * 2',
                'description' => 'Маг создает щит, поглощающий урон в размере интеллекта x2. Щит висит, пока не будет пробит.',
            ],
            [
                'class' => 'маг',
                'ability_name' => 'Огненный шар',
                'ability_type' => 'attack',
                'mp_cost' => 20,
                'gold_cost' => 35,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 1,
                'duration' => 1,
                'level_required' => 4,
                'effect_type' => 'deal_damage',
                'effect_formula' => 'intelligence * 2.5',
                'description' => 'Огненный шар, наносящий 250% урона от Интеллекта.',
            ],
        ];

        $validAbilityIds = [];
        foreach ($abilities as $abilityData) {
            $ability = ClassAbility::updateOrCreate(
                ['class' => $abilityData['class'], 'ability_name' => $abilityData['ability_name']],
                $abilityData
            );
            $validAbilityIds[] = $ability->id;
        }

        // Удаляем способности, которых нет в текущем списке сидера
        ClassAbility::whereNotIn('id', $validAbilityIds)->delete();
    }
}
