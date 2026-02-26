<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combat;
use App\Models\Enemy;
use App\Services\CombatService;
use App\Services\CharacterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CombatController extends Controller
{
    protected CombatService $combatService;
    protected CharacterService $characterService;
    protected \App\Services\AbilityService $abilityService;

    public function __construct(
        CombatService $combatService,
        CharacterService $characterService,
        \App\Services\AbilityService $abilityService
    ) {
        $this->combatService = $combatService;
        $this->characterService = $characterService;
        $this->abilityService = $abilityService;
    }

    /**
     * Начать бой
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            'enemy_ids' => 'nullable|array|min:1',
            'enemy_ids.*' => 'exists:enemies,id',
        ]);

        $character = $this->characterService->getById($request->character_id);

        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        $enemyIds = $request->enemy_ids;
        $enemyLevels = [];

        // Если враги не указаны — выбираем случайного по уровню глубины
        if (empty($enemyIds)) {
            $depth = $character->dungeon_depth;
            // Определяем целевой уровень врага (Depth +/- 1)
            $targetLevel = max(1, $depth + rand(-1, 1));
            
            // Ищем шаблон врага, который ближе всего к этому уровню и доступен на этой глубине
            $randomEnemy = Enemy::where('min_depth', '<=', $depth)
                ->where(function($query) use ($depth) {
                    $query->where('max_depth', '>=', $depth)
                          ->orWhereNull('max_depth');
                })
                ->orderByRaw("ABS(level - $targetLevel)")
                ->inRandomOrder()
                ->first();

            if (!$randomEnemy) {
                return response()->json(['message' => 'Враги не найдены'], 404);
            }

            $enemyIds = [$randomEnemy->id];
            $enemyLevels = [$targetLevel];
        } else {
            // Если ID переданы вручную, используем уровни из БД
            foreach ($enemyIds as $id) {
                $e = Enemy::find($id);
                $enemyLevels[] = $e ? $e->level : 1;
            }
        }

        if ($character->dynamicStats->is_in_combat) {
            // ... (предыдущий код без изменений до $combat = ...)
        }

        try {
            $combat = $this->combatService->startCombat($character, $enemyIds, $enemyLevels);
            
            return response()->json([
                'success' => true,
                'data' => $combat->load(['participants.enemy', 'character.dynamicStats', 'character.stats'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Совершить атаку
     *
     * @param Request $request
     * @param Combat $combat
     * @return JsonResponse
     */
    public function attack(Request $request, Combat $combat): JsonResponse
    {
        $request->validate([
            'target_id' => 'required|exists:combat_participants,id',
        ]);

        if ($combat->character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш бой'], 403);
        }

        if ($combat->status !== 'active') {
             return response()->json(['message' => 'Бой уже завершен'], 400);
        }

        try {
            $result = $this->combatService->performAttack($combat, $request->target_id);
            $result['combat'] = $combat->load(['participants.enemy', 'character.dynamicStats', 'character.stats']);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Использовать защитную стойку/способность
     *
     * @param Request $request
     * @param Combat $combat
     * @return JsonResponse
     */
    public function defense(Request $request, Combat $combat): JsonResponse
    {
        if ($combat->character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш бой'], 403);
        }

        if ($combat->status !== 'active') {
             return response()->json(['message' => 'Бой уже завершен'], 400);
        }

        try {
            $result = $this->combatService->performDefense($combat);
            $result['combat'] = $combat->load(['participants.enemy', 'character.dynamicStats', 'character.stats']);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Использовать конкретную способность
     */
    public function useAbility(Request $request, Combat $combat): JsonResponse
    {
        $request->validate([
            'ability_id' => 'required|integer',
            'target_id' => 'nullable|integer'
        ]);

        if ($combat->character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш бой'], 403);
        }

        if ($combat->status !== 'active') {
             return response()->json(['message' => 'Бой уже завершен'], 400);
        }

        try {
            $result = $this->combatService->performAbility($combat, $request->ability_id, $request->target_id);
            $result['combat'] = $combat->load(['participants.enemy', 'character.dynamicStats', 'character.stats']);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Получить список доступных навыков для персонажа
     */
    public function abilities(Request $request, \App\Models\Character $character): JsonResponse
    {
        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        $abilities = $this->abilityService->getAvailableAbilities($character);
        return response()->json(['success' => true, 'data' => $abilities]);
    }

    /**
     * Попытаться сбежать
     *
     * @param Request $request
     * @param Combat $combat
     * @return JsonResponse
     */
    public function flee(Request $request, Combat $combat): JsonResponse
    {
        if ($combat->character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш бой'], 403);
        }

         if ($combat->status !== 'active') {
             return response()->json(['message' => 'Бой уже завершен'], 400);
        }

        try {
            $result = $this->combatService->performFlee($combat);
            $result['combat'] = $combat->load(['participants.enemy', 'character.dynamicStats', 'character.stats']);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Получить состояние боя
     */
    public function show(Combat $combat): JsonResponse
    {
        if ($combat->character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш бой'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $combat->load(['participants.enemy', 'character.dynamicStats', 'character.stats'])
        ]);
    }

    /**
     * Получить активный бой персонажа
     */
    public function active(int $characterId): JsonResponse
    {
        $character = $this->characterService->getById($characterId);
        
        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        $combat = Combat::where('character_id', $characterId)
            ->where('status', 'active')
            ->with(['participants.enemy', 'character.dynamicStats', 'character.stats'])
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $combat
        ]);
    }

    /**
     * Спуститься глубже в подземелье
     */
    public function goDeeper(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
        ]);

        $character = $this->characterService->getById($request->character_id);

        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        $dynamic = $character->dynamicStats;

        if ($dynamic->enemies_defeated_at_depth < 3) {
            return response()->json([
                'success' => false, 
                'message' => 'Нужно победить еще ' . (3 - $dynamic->enemies_defeated_at_depth) . ' врагов'
            ], 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($character, $dynamic) {
            $character->increment('dungeon_depth');
            if ($character->dungeon_depth > $character->max_dungeon_depth) {
                $character->max_dungeon_depth = $character->dungeon_depth;
                $character->save();
            }
            $dynamic->update(['enemies_defeated_at_depth' => 0]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Вы спустились глубже!',
            'data' => [
                'dungeon_depth' => $character->fresh()->dungeon_depth,
                'max_dungeon_depth' => $character->fresh()->max_dungeon_depth,
                'enemies_defeated_at_depth' => 0
            ]
        ]);
    }

    /**
     * Сменить текущую глубину подземелья
     */
    public function changeDepth(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            'depth' => 'required|integer|min:1',
        ]);

        $character = $this->characterService->getById($request->character_id);

        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        if ($request->depth > $character->max_dungeon_depth) {
            return response()->json([
                'success' => false,
                'message' => 'Эта глубина еще не разблокирована (Макс: ' . $character->max_dungeon_depth . ')'
            ], 400);
        }

        $character->update(['dungeon_depth' => $request->depth]);

        return response()->json([
            'success' => true,
            'message' => 'Вы перешли на глубину ' . $request->depth,
            'data' => [
                'dungeon_depth' => $character->dungeon_depth
            ]
        ]);
    }
}
