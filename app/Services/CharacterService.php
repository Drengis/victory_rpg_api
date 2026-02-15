<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterStat;
use App\Models\CharacterDynamicStat;
use App\Services\Core\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CharacterService extends BaseService
{
    protected function getModel(): string
    {
        return Character::class;
    }

    /**
     * Создать нового персонажа
     */
    public function createCharacter(array $data): Character
    {
        $validClasses = ['воин', 'лучник', 'маг'];
        $class = mb_strtolower($data['class'] ?? '');
        
        if (!in_array($class, $validClasses)) {
            throw new \Exception("Невалидный класс персонажа.");
        }

        return DB::transaction(function () use ($data) {
            $character = Character::create([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'class' => $data['class'],
                // Базовые статы будут 5 по умолчанию из БД, 
                // но укажем явно для чистоты
                'strength' => 5,
                'agility' => 5,
                'constitution' => 5,
                'intelligence' => 5,
                'luck' => 5,
            ]);

            $this->syncStats($character);
            
            return $character;
        });
    }

    /**
     * Расчитать финальные характеристики персонажа
     * @param Character $character
     * @return array
     */
    public function calculateFinalStats(Character $character): array
    {
        $baseStats = [
            'strength' => $character->strength,
            'agility' => $character->agility,
            'constitution' => $character->constitution,
            'intelligence' => $character->intelligence,
            'luck' => $character->luck,
        ];

        $modifiers = $this->getClassModifiers($character->class);
        
        $modifiedStats = [];
        foreach ($baseStats as $stat => $value) {
            $added = $character->{$stat . '_added'} ?? 0;
            $mod = $modifiers[$stat] ?? 0;
            $modifiedStats[$stat] = round(($value + $added) * (1 + $mod / 100));
        }

        return [
            'base_stats' => $baseStats,
            'class_modifiers' => $modifiers,
            'final_stats' => $modifiedStats,
            'derived_stats' => $this->calculateDerivedStats($character, $modifiedStats),
        ];
    }

    /**
     * Получить модификаторы для класса
     */
    private function getClassModifiers(string $class): array
    {
        return match (mb_strtolower($class)) {
            'воин' => ['strength' => 10, 'intelligence' => -10],
            'лучник' => ['agility' => 10, 'strength' => -10],
            'маг' => ['intelligence' => 10, 'constitution' => -10],
            default => [],
        };
    }

    /**
     * Расчитать производные параметры
     */
    private function calculateDerivedStats(Character $character, array $finalStats): array
    {
        $mainStatBonus = $this->getMainStatBonus($character->class, $finalStats);

        return [
            'hp' => $finalStats['constitution'] * 10,
            'hp_regen' => $finalStats['constitution'] * 0.5,
            'mana' => $finalStats['intelligence'] * 15,
            'mana_regen' => $finalStats['intelligence'] * 0.2,
            
            // Урон (Damage): 
            // Сила дает 1% к физ. урону всем. Воин получает еще +1% от силы (итого 2%).
            // Ловкость дает +1% к физ. урону для Лучника.
            'physical_damage_bonus' => ($finalStats['strength'] * 1) 
                                        + ($this->isMainStat($character->class, 'strength') ? $finalStats['strength'] * 1 : 0)
                                        + ($this->isMainStat($character->class, 'agility') ? $finalStats['agility'] * 1 : 0),
                                        
            // Интеллект дает 1% к маг. урону всем. Маг получает еще +1% (итого 2%).
            'magical_damage_bonus' => ($finalStats['intelligence'] * 1) 
                                        + ($this->isMainStat($character->class, 'intelligence') ? $finalStats['intelligence'] * 1 : 0),
            
            // Попадание (Accuracy): Ловкость +1.5%. Ключевой стат еще +0.5%.
            'accuracy' => ($finalStats['agility'] * 1.5) + ($mainStatBonus['accuracy'] ?? 0),
            
            // Уклонение (Evasion): Ловкость +1%, Удача +0.5.
            'evasion' => ($finalStats['agility'] * 1.0) + ($finalStats['luck'] * 0.5),
            
            // Крит: Ловкость +0.3%, Удача +0.1%.
            'crit_chance' => ($finalStats['agility'] * 0.3) + ($finalStats['luck'] * 0.1),
            
            // Редкий лут: Удача +1% (множитель 0.01)
            'rare_loot_bonus' => $finalStats['luck'] * 0.01,
        ];
    }

    /**
     * Проверка, является ли характеристика основной для класса
     */
    private function isMainStat(string $class, string $stat): bool
    {
        $mainStats = [
            'воин' => 'strength',
            'лучник' => 'agility',
            'маг' => 'intelligence',
        ];

        return ($mainStats[mb_strtolower($class)] ?? '') === $stat;
    }

    /**
     * Ключевая характеристика дает дополнительные бонусы
     */
    private function getMainStatBonus(string $class, array $finalStats): array
    {
        $class = mb_strtolower($class);
        $mainStatValue = 0;

        if ($class === 'воин') $mainStatValue = $finalStats['strength'];
        elseif ($class === 'лучник') $mainStatValue = $finalStats['agility'];
        elseif ($class === 'маг') $mainStatValue = $finalStats['intelligence'];

        return [
            'damage' => $mainStatValue * 1.0, // +1% к урону
            'accuracy' => $mainStatValue * 0.5, // +0.5% к попаданию
        ];
    }

    /**
     * Синхронизировать вычисляемые характеристики персонажа
     */
    public function syncStats(Character $character): void
    {
        $calculated = $this->calculateFinalStats($character);
        $derived = $calculated['derived_stats'];

        CharacterStat::updateOrCreate(
            ['character_id' => $character->id],
            [
                'max_hp' => $derived['hp'],
                'hp_regen' => $derived['hp_regen'],
                'max_mp' => $derived['mana'],
                'mp_regen' => $derived['mana_regen'],
                'physical_damage_bonus' => $derived['physical_damage_bonus'],
                'magical_damage_bonus' => $derived['magical_damage_bonus'],
                'accuracy' => $derived['accuracy'],
                'evasion' => $derived['evasion'],
                'crit_chance' => $derived['crit_chance'],
                'rare_loot_bonus' => $derived['rare_loot_bonus'],
            ]
        );

        // Инициализация динамических статов, если их нет
        if (!$character->dynamicStats()->exists()) {
            $character->dynamicStats()->create([
                'current_hp' => $derived['hp'],
                'current_mp' => $derived['mana'],
                'last_regen_at' => now(),
            ]);
        }
    }

    /**
     * Обновить динамические показатели (регенерация)
     */
    public function refreshDynamicStats(Character $character): CharacterDynamicStat
    {
        return DB::transaction(function () use ($character) {
            $dynamic = $character->dynamicStats;
            $stats = $character->stats;

            if (!$dynamic || !$stats) {
                $this->syncStats($character);
                $character->load(['stats', 'dynamicStats']);
                $dynamic = $character->dynamicStats;
                $stats = $character->stats;
            }

            $now = Carbon::now();
            $secondsPassed = $now->diffInSeconds($dynamic->last_regen_at);
            $secondsPassed = abs($secondsPassed);

            // Если персонаж в бою, временная регенерация не начисляется
            if ($dynamic->is_in_combat) {
                return $dynamic;
            }

            if ($secondsPassed > 0) {
                // Регенерация HP
                if ($dynamic->current_hp < $stats->max_hp) {
                    $regenHp = ($stats->hp_regen / 60) * $secondsPassed;
                    $dynamic->current_hp = min($stats->max_hp, $dynamic->current_hp + $regenHp);
                }

                // Регенерация MP
                if ($dynamic->current_mp < $stats->max_mp) {
                    $regenMp = ($stats->mp_regen / 60) * $secondsPassed;
                    $dynamic->current_mp = min($stats->max_mp, $dynamic->current_mp + $regenMp);
                }

                $dynamic->last_regen_at = $now;
                $dynamic->save();
            }

            return $dynamic;
        });
    }

    /**
     * Начислить регенерацию за один раунд боя
     */
    public function applyCombatRoundRegen(Character $character): CharacterDynamicStat
    {
        return DB::transaction(function () use ($character) {
            $dynamic = $character->dynamicStats;
            $stats = $character->stats;

            if (!$dynamic || !$stats) {
                return $this->refreshDynamicStats($character);
            }

            // 1 раунд условно = 6 секунд (1/10 минуты)
            $roundFactor = 6 / 60;

            if ($dynamic->current_hp < $stats->max_hp) {
                $regenHp = $stats->hp_regen * $roundFactor;
                $dynamic->current_hp = min($stats->max_hp, $dynamic->current_hp + $regenHp);
            }

            if ($dynamic->current_mp < $stats->max_mp) {
                $regenMp = $stats->mp_regen * $roundFactor;
                $dynamic->current_mp = min($stats->max_mp, $dynamic->current_mp + $regenMp);
            }

            $dynamic->last_regen_at = Carbon::now();
            $dynamic->save();

            return $dynamic;
        });
    }

    /**
     * Рассчитать опыт для следующего уровня.
     * Формула: XPn=100+30*(n-1)+10*(n-1)^2
     */
    public function calculateXpForLevel(int $level): int
    {
        if ($level < 1) return 100;
        $n_minus_1 = $level - 1;
        return 100 + (30 * $n_minus_1) + (10 * ($n_minus_1 ** 2));
    }

    /**
     * Добавить опыт персонажу
     */
    public function addExperience(Character $character, int $amount): void
    {
        $character->experience += $amount;

        $xpNeeded = $this->calculateXpForLevel($character->level);

        while ($character->experience >= $xpNeeded) {
            $character->experience -= $xpNeeded;
            $character->level++;
            $character->stat_points += 3;
            $xpNeeded = $this->calculateXpForLevel($character->level);
        }

        $character->save();
        $this->syncStats($character);
    }

    /**
     * Распределить очки характеристик
     * @param string $stat (strength, agility, constitution, intelligence, luck)
     */
    public function distributeStatPoint(Character $character, string $stat): void
    {
        if ($character->stat_points <= 0) {
            throw new \Exception("Недостаточно очков характеристик.");
        }

        $validStats = ['strength', 'agility', 'constitution', 'intelligence', 'luck'];
        if (!in_array($stat, $validStats)) {
            throw new \Exception("Невалидная характеристика.");
        }

        // Валидация: Максимум 2 очка в одну характеристику за уровень.
        // Это значит, что для уровня L, сумма вложенных очков в одну стату не может превышать (L-1) * 2.
        $addedField = $stat . '_added';
        $currentAdded = $character->{$addedField};
        $limit = ($character->level - 1) * 2;

        if ($currentAdded >= $limit) {
            throw new \Exception("Достигнут предел прокачки этой характеристики для текущего уровня.");
        }

        $character->{$addedField}++;
        $character->stat_points--;
        $character->save();

        $this->syncStats($character);
    }
}
