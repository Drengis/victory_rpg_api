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

        // Получаем реальные ID материалов по имени
        $ratTailId = \App\Models\Item::where('name', 'Хвост крысы')->value('id');
        $goblinEarId = \App\Models\Item::where('name', 'Ухо гоблина')->value('id');
        $skeletonBoneId = \App\Models\Item::where('name', 'Кость скелета')->value('id');
        $orcFangId = \App\Models\Item::where('name', 'Клык орка')->value('id');

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
            'requirements' => ['level' => 1],
            'rewards' => ['gold' => 150],
        ]);
        $q6 = \App\Models\Quest::create([
            'name' => 'Проблема с грызунами',
            'description' => 'Некоторые жители жалуются на расплодившихся крыс. Принесите им доказательства охоты.',
            'type' => 'loot',
            'target_value' => 5,
            'requirements' => ['level' => 1, 'quest_id' => $q1->id, 'item_id' => $ratTailId],
            'rewards' => ['gold' => 40, 'xp' => 40],
        ]);

        $q7 = \App\Models\Quest::create([
            'name' => 'Зачистка лагеря гоблинов',
            'description' => 'Гоблины стали слишком смелыми. Принесите трофеи, чтобы доказать свою доблесть.',
            'type' => 'loot',
            'target_value' => 5,
            'requirements' => ['level' => 5, 'item_id' => $goblinEarId],
            'rewards' => ['gold' => 80, 'xp' => 150, 'random_mid_gear' => true],
        ]);

        $q8 = \App\Models\Quest::create([
            'name' => 'Некроманты не дремлют',
            'description' => 'Из-под земли восстают скелеты. Кто-то за это платит...',
            'type' => 'loot',
            'target_value' => 10,
            'requirements' => ['level' => 7, 'item_id' => $skeletonBoneId],
            'rewards' => ['gold' => 100, 'xp' => 300, 'random_mid_gear' => true],
        ]);

        $q9 = \App\Models\Quest::create([
            'name' => 'Орочья угроза',
            'description' => 'Орки приближаются! Уничтожьте их разведчиков.',
            'type' => 'loot',
            'target_value' => 3,
            'requirements' => ['level' => 10, 'item_id' => $orcFangId],
            'rewards' => ['gold' => 220, 'xp' => 500, 'stat_points' => 1],
        ]);

        $q10 = \App\Models\Quest::create([
            'name' => 'Дератизация: Профи',
            'description' => 'Крысы никуда не исчезли. Нужно радикальное решение проблемы.',
            'type' => 'loot',
            'target_value' => 15,
            'requirements' => ['level' => 3, 'quest_id' => $q6->id, 'item_id' => $ratTailId],
            'rewards' => ['gold' => 100, 'random_mid_gear' => true],
        ]);
    }
}
