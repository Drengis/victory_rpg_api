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

    public function __construct(CombatService $combatService, CharacterService $characterService)
    {
        $this->combatService = $combatService;
        $this->characterService = $characterService;
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
            'enemy_ids' => 'required|array|min:1',
            'enemy_ids.*' => 'exists:enemies,id',
        ]);

        $character = $this->characterService->getById($request->character_id);

        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        if ($character->dynamicStats->is_in_combat) {
             $existingCombat = Combat::where('character_id', $character->id)
                ->where('status', 'active')
                ->latest()
                ->first();
             
             if ($existingCombat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы уже в бою',
                    'data' => $existingCombat->load(['participants.enemy', 'character'])
                ], 400);
             }
        }

        try {
            $combat = $this->combatService->startCombat($character, $request->enemy_ids);
            
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
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
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
}
