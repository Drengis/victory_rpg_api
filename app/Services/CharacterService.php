<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterStat;
use App\Models\CharacterDynamicStat;
use App\Models\CharacterItem;
use App\Models\Item;
use App\Services\Core\BaseService;
use App\Services\QuestService;
use App\Traits\CalculatesDerivedStats;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CharacterService extends BaseService
{
    use CalculatesDerivedStats;
    protected QuestService $questService;

    protected function getModel(): string
    {
        return Character::class;
    }

    public function __construct(QuestService $questService)
    {
        $this->questService = $questService;
    }

    /**
     * Переопределяем метод создания для автоматической синхронизации стат
     */
    public function create(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $character = parent::create($data);
            $character->refresh();
            $this->syncStats($character);
            return $character;
        });
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
        $modifiers = $this->getClassModifiers($character->class);
        $baseStats = [
            'strength' => $character->strength,
            'agility' => $character->agility,
            'constitution' => $character->constitution,
            'intelligence' => $character->intelligence,
            'luck' => $character->luck,
        ];

        // 1. Собираем статы со шмота
        $gearStats = [
            'strength' => 0,
            'agility' => 0,
            'constitution' => 0,
            'intelligence' => 0,
            'luck' => 0,
            'armor' => 0,
        ];

        $weapon = null;
        $equippedItems = $character->equippedItems()->with('item')->get();

        foreach ($equippedItems as $charItem) {
            $item = $charItem->item;
            if ($item->type === 'weapon') {
                $weapon = $item;
            }

            foreach ($gearStats as $stat => $value) {
                $gearStats[$stat] += $item->getBonus($stat, $charItem->ilevel);
            }
        }

        // 2. Складываем Базу + Вложенные очки + Шмот
        $modifiedStats = [];
        foreach ($baseStats as $stat => $value) {
            $added = $character->{$stat . '_added'} ?? 0;
            $gear = $gearStats[$stat];
            
            // Сначала складываем всё, потом применяем классовый множитель
            $mod = $modifiers[$stat] ?? 0;
            $modifiedStats[$stat] = round(($value + $added + $gear) * (1 + $mod / 100));
        }

        // 3. Расчет боевых параметров на основе итоговых стат
        $stats = $this->getDerivedStats($modifiedStats);
        
        // Инициализация бонусов урона (Универсальные бонусы)
        $stats['physical_damage_bonus'] = $modifiedStats['strength'] * 1; // 1% за 1 силы всем
        $stats['magical_damage_bonus'] = $modifiedStats['intelligence'] * 1; // 1% за 1 инт всем

        // Классовые бонусы к урону и точности
        $class = mb_strtolower($character->class);
        if ($class === 'воин') {
            $stats['physical_damage_bonus'] += $modifiedStats['strength'] * 1; // Итого 2%
            $stats['accuracy'] += $modifiedStats['strength'] * 0.5;
        } elseif ($class === 'лучник') {
            $stats['accuracy'] += $modifiedStats['agility'] * 2;
            $stats['physical_damage_bonus'] += $modifiedStats['agility'] * 1;
        } elseif ($class === 'маг') {
            $stats['magical_damage_bonus'] += $modifiedStats['intelligence'] * 1; // Итого 2%
            $stats['accuracy'] += $modifiedStats['intelligence'] * 0.5;
        }

        // 4. Итоговый урон оружия
        if ($weapon) {
            // Ищем конкретный экземпляр оружия, чтобы узнать его ilevel
            $charWeapon = $equippedItems->first(fn($ci) => $ci->item_id === $weapon->id);
            $ilevel = $charWeapon ? $charWeapon->ilevel : 1;

            // Применяем iLvl и редкость к урону оружия
            $baseMin = $weapon->getBonus('min_damage', $ilevel);
            $baseMax = $weapon->getBonus('max_damage', $ilevel);

            // Для мага используем magical_damage_bonus, для остальных physical_damage_bonus
            $class = mb_strtolower($character->class);
            $damageBonus = ($class === 'маг') ? $stats['magical_damage_bonus'] : $stats['physical_damage_bonus'];
            
            $stats['min_damage'] = round($baseMin * (1 + $damageBonus / 100));
            $stats['max_damage'] = round($baseMax * (1 + $damageBonus / 100));
        } else {
            // Значения по умолчанию для урона (если нет оружия)
            // Для мага - урон от интеллекта, для остальных - базовый
            $class = mb_strtolower($character->class);
            if ($class === 'маг') {
                $intBonus = ($modifiedStats['intelligence'] ?? 1) * 2;
                $stats['min_damage'] = 2 + $intBonus;
                $stats['max_damage'] = 5 + $intBonus;
            } else {
                $stats['min_damage'] = 3;
                $stats['max_damage'] = 7;
            }
        }

        $stats['armor'] = ($gearStats['armor'] ?? 0) + ($stats['armor'] ?? 0);
        // 5. Применение пассивных навыков
        $passives = $character->abilities()->where('ability_type', 'passive')->get();
        foreach ($passives as $passive) {
            $formula = $passive->effect_formula;
            // Просто парсим формулу вида "stat * 0.X"
            if (preg_match('/([a-z_]+)\s*\*\s*([0-9.]+)/', $formula, $matches)) {
                $targetStat = $matches[1];
                $multiplier = (float)$matches[2];
                
                if (isset($stats[$targetStat])) {
                    $stats[$targetStat] += $stats[$targetStat] * $multiplier;
                } elseif (isset($modifiedStats[$targetStat])) {
                    // Если стат еще в модифицированных (базовых), применяем там
                    $modifiedStats[$targetStat] += $modifiedStats[$targetStat] * $multiplier;
                }
            }
        }

        // Финальное округление всех статов
        foreach ($stats as $key => $val) {
            $stats[$key] = round($val, 2);
        }

        return array_merge($modifiedStats, $stats);
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
     * Расчитать производные параметры (уже не используется напрямую, см. getDerivedStats в трейте)
     */

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

        CharacterStat::updateOrCreate(
            ['character_id' => $character->id],
            [
                'strength' => $calculated['strength'],
                'agility' => $calculated['agility'],
                'constitution' => $calculated['constitution'],
                'intelligence' => $calculated['intelligence'],
                'luck' => $calculated['luck'],
                'max_hp' => $calculated['max_hp'],
                'hp_regen' => $calculated['hp_regen'],
                'max_mp' => $calculated['max_mp'],
                'mp_regen' => $calculated['mp_regen'],
                'physical_damage_bonus' => $calculated['physical_damage_bonus'],
                'magical_damage_bonus' => $calculated['magical_damage_bonus'],
                'accuracy' => $calculated['accuracy'],
                'evasion' => $calculated['evasion'],
                'crit_chance' => $calculated['crit_chance'],
                'rare_loot_bonus' => $calculated['rare_loot_bonus'],
                'min_damage' => $calculated['min_damage'],
                'max_damage' => $calculated['max_damage'],
                'armor' => $calculated['armor'],
            ]
        );

        // Инициализация динамических статов, если их нет
        if (!$character->dynamicStats()->exists()) {
            $character->dynamicStats()->create([
                'current_hp' => $calculated['max_hp'],
                'current_mp' => $calculated['max_mp'],
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

        // Обновляем прогресс квестов на уровень
        $this->questService->updateProgress($character, 'level_up', 0);
    }

    /**
     * Распределить очки характеристик
     * @param string $stat (strength, agility, constitution, intelligence, luck)
     */
    public function distributeStatPoint(Character $character, string $stat): void
    {
        $validStats = ['strength', 'agility', 'constitution', 'intelligence', 'luck'];
        if (!in_array($stat, $validStats)) {
            throw new \Exception("Невалидная характеристика.");
        }

        DB::transaction(function () use ($character, $stat) {
            // Блокируем запись персонажа для обновления
            $lockedCharacter = Character::where('id', $character->id)->lockForUpdate()->first();

            if ($lockedCharacter->stat_points <= 0) {
                throw new \Exception("Недостаточно очков характеристик.");
            }

            // Валидация: Максимум 2 очка в одну характеристику за уровень.
            $addedField = $stat . '_added';
            $currentAdded = $lockedCharacter->{$addedField};
            $limit = ($lockedCharacter->level - 1) * 2;

            if ($currentAdded >= $limit) {
                throw new \Exception("Достигнут предел прокачки этой характеристики для текущего уровня.");
            }

            // Обновляем заблокированную модель
            $lockedCharacter->{$addedField}++;
            $lockedCharacter->stat_points--;
            $lockedCharacter->save();

            // Синхронизируем состояние исходного объекта, чтобы контроллер видел изменения
            $character->setRawAttributes($lockedCharacter->getAttributes(), true);
        });

        $this->syncStats($character);
    }

    /**
     * Надеть предмет
     */
    public function equipItem(Character $character, CharacterItem $charItem, string $slot): void
    {
        $item = $charItem->item;
        if ($item->required_class && mb_strtolower($item->required_class) !== mb_strtolower($character->class)) {
            throw new \Exception("Этот предмет предназначен для класса: {$item->required_class}.");
        }

        DB::transaction(function () use ($character, $charItem, $slot) {
            // Сначала снимаем всё, что в этом слоте
            $character->items()
                ->where('slot', $slot)
                ->where('is_equipped', true)
                ->update(['is_equipped' => false, 'slot' => null]);

            $charItem->update([
                'slot' => $slot,
                'is_equipped' => true,
            ]);

            $this->syncStats($character);
        });
    }

    /**
     * Снять предмет
     */
    public function unequipItem(Character $character, CharacterItem $charItem): void
    {
        $charItem->update([
            'is_equipped' => false,
            'slot' => null,
        ]);

        $this->syncStats($character);
    }

    /**
     * Добавить предмет в инвентарь персонажа
     */
    public function addItemToCharacter(Character $character, Item $item, int $quantity = 1, int $ilevel = 1, ?int $quality = null): void
    {
        if ($quantity <= 0) return;

        DB::transaction(function () use ($character, $item, $quantity, $ilevel, $quality) {
            // Если предмет стакается (материалы, расходники)
            if (in_array($item->type, ['material', 'junk', 'consumable', 'resource'])) {
                $existing = CharacterItem::where('character_id', $character->id)
                    ->where('item_id', $item->id)
                    ->where('ilevel', $ilevel)
                    ->first();

                if ($existing) {
                    $existing->quantity += $quantity;
                    $existing->save();
                } else {
                    CharacterItem::create([
                        'character_id' => $character->id,
                        'item_id' => $item->id,
                        'ilevel' => $ilevel,
                        'quantity' => $quantity,
                        'quality' => $quality ?? $item->quality,
                        'is_equipped' => false,
                    ]);
                }
            } else {
                // Экипировка всегда создается отдельными записями
                for ($i = 0; $i < $quantity; $i++) {
                    CharacterItem::create([
                        'character_id' => $character->id,
                        'item_id' => $item->id,
                        'ilevel' => $ilevel,
                        'quantity' => 1,
                        'quality' => $quality ?? $item->quality,
                        'is_equipped' => false,
                    ]);
                }
            }

            // После добавления предмета обновляем прогресс квестов типа "loot"
            $this->questService->updateProgress($character, 'loot', 0);
        });
    }
}
