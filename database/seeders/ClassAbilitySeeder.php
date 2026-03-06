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
            [
                'class' => 'воин',
                'ability_name' => 'Удар щитом',
                'ability_type' => 'attack',
                'mp_cost' => 12,
                'gold_cost' => 25,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 2,
                'duration' => 1,
                'level_required' => 2,
                'effect_type' => 'deal_damage',
                'effect_formula' => 'strength * 0.6',
                'description' => 'Удар щитом, наносящий 60% урона от силы. Имеет 60% шанс оглушить врага на 1 ход.',
                'effects' => [['type' => 'stun', 'chance' => 60, 'duration' => 1]],
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
            [
                'class' => 'лучник',
                'ability_name' => 'Кровоточащая стрела',
                'ability_type' => 'attack',
                'mp_cost' => 12,
                'gold_cost' => 25,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 1,
                'duration' => 1,
                'level_required' => 2,
                'effect_type' => 'deal_damage',
                'effect_formula' => 'agility * 0.5',
                'description' => 'Стрела, наносящая 50% урона от ловкости и вызывающая кровотечение на 3 хода.',
                'effects' => [['type' => 'bleed', 'chance' => 100, 'duration' => 3]],
            ],
            // Маг
            [
                'class' => 'маг',
                'ability_name' => 'Магический щит',
                'ability_type' => 'defense',
                'mp_cost' => 15,
                'gold_cost' => 15,
                'max_uses_per_combat' => 1,
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
            [
                'class' => 'маг',
                'ability_name' => 'Поджог',
                'ability_type' => 'attack',
                'mp_cost' => 14,
                'gold_cost' => 25,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 1,
                'duration' => 1,
                'level_required' => 2,
                'effect_type' => 'deal_damage',
                'effect_formula' => 'intelligence * 0.6',
                'description' => 'Поджигает врага, нанося 60% урона от интеллекта и накладывая эффект горения на 3 хода.',
                'effects' => [['type' => 'burn', 'chance' => 100, 'duration' => 3]],
            ],
            // ПАССИВНЫЕ НАВЫКИ
            // Воин
            [
                'class' => 'воин',
                'ability_name' => 'Крепость',
                'ability_type' => 'passive',
                'mp_cost' => 0,
                'gold_cost' => 50,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 0,
                'level_required' => 2,
                'effect_type' => 'stat_boost',
                'effect_formula' => 'armor * 0.1',
                'description' => 'Постоянно увеличивает показатель брони на 10%.',
            ],
            [
                'class' => 'воин',
                'ability_name' => 'Закалка',
                'ability_type' => 'passive',
                'mp_cost' => 0,
                'gold_cost' => 75,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 0,
                'level_required' => 4,
                'effect_type' => 'stat_boost',
                'effect_formula' => 'max_hp * 0.15',
                'description' => 'Постоянно увеличивает максимальный запас здоровья на 15%.',
            ],
            // Лучник
            [
                'class' => 'лучник',
                'ability_name' => 'Точность',
                'ability_type' => 'passive',
                'mp_cost' => 0,
                'gold_cost' => 50,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 0,
                'level_required' => 2,
                'effect_type' => 'stat_boost',
                'effect_formula' => 'accuracy * 0.1',
                'description' => 'Постоянно увеличивает меткость на 10%.',
            ],
            [
                'class' => 'лучник',
                'ability_name' => 'Ускорение',
                'ability_type' => 'passive',
                'mp_cost' => 0,
                'gold_cost' => 75,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 0,
                'level_required' => 4,
                'effect_type' => 'stat_boost',
                'effect_formula' => 'evasion * 0.1',
                'description' => 'Постоянно увеличивает уклонение на 10%.',
            ],
            // Маг
            [
                'class' => 'маг',
                'ability_name' => 'Медитация',
                'ability_type' => 'passive',
                'mp_cost' => 0,
                'gold_cost' => 50,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 0,
                'level_required' => 2,
                'effect_type' => 'stat_boost',
                'effect_formula' => 'mp_regen * 0.2',
                'description' => 'Постоянно увеличивает регенерацию маны на 20%.',
            ],
            [
                'class' => 'маг',
                'ability_name' => 'Тайные знания',
                'ability_type' => 'passive',
                'mp_cost' => 0,
                'gold_cost' => 100,
                'max_uses_per_combat' => null,
                'cooldown_turns' => 0,
                'duration' => 0,
                'level_required' => 6,
                'effect_type' => 'stat_boost',
                'effect_formula' => 'magical_damage_bonus * 0.1',
                'description' => 'Постоянно увеличивает бонус магического урона на 10%.',
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
