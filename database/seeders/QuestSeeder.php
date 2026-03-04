<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Очищаем существующие квесты и прогресс
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \App\Models\Quest::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $q1 = \App\Models\Quest::create([
            'name' => 'Первые шаги',
            'description' => 'Мир полон опасностей. Убейте своих первых врагов, чтобы доказать свою силу.',
            'type' => 'kills',
            'target_value' => 5,
            'requirements' => ['level' => 1],
            'rewards' => ['gold' => 20, 'xp' => 50],
        ]);

        $q2 = \App\Models\Quest::create([
            'name' => 'Исследователь глубин',
            'description' => 'Подземелье зовет. Спуститесь на 3-ю глубину, чтобы найти более ценные сокровища.',
            'type' => 'depth',
            'target_value' => 3,
            'requirements' => ['quest_id' => $q1->id],
            'rewards' => ['gold' => 25, 'xp' => 60],
        ]);

        $q3 = \App\Models\Quest::create([
            'name' => 'Охотник на монстров',
            'description' => 'Вы уже не новичок. Пора заняться серьезной охотой.',
            'type' => 'kills',
            'target_value' => 20,
            'requirements' => ['level' => 3],
            'rewards' => ['gold' => 100, 'xp' => 200],
        ]);

        $q4 = \App\Models\Quest::create([
            'name' => 'Собиратель богатств',
            'description' => 'Настоящий герой должен уметь копить ресурсы. Соберите 1000 золота.',
            'type' => 'gold',
            'target_value' => 1000,
            'requirements' => ['quest_id' => $q2->id],
            'rewards' => ['xp' => 100, 'stat_points' => 2],
        ]);

        $q5 = \App\Models\Quest::create([
            'name' => 'Становление силы',
            'description' => 'Ваш путь только начинается. Достигните 5-го уровня.',
            'type' => 'level',
            'target_value' => 5,
            'requirements' => ['level' => 4],
            'rewards' => ['gold' => 150],
        ]);
    }
}
