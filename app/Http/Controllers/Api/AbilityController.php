<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\ClassAbility;
use App\Services\AbilityService;
use App\Services\CharacterService;
use App\Services\CurrencyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AbilityController extends Controller
{
    public function __construct(
        protected AbilityService $abilityService,
        protected CharacterService $characterService,
        protected CurrencyService $currencyService
    ) {}

    /**
     * Получить список навыков для изучения
     */
    public function index(Character $character): JsonResponse
    {
        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        $allClassAbilities = ClassAbility::where('class', mb_strtolower($character->class))->get();
        $ownedAbilityIds = $character->abilities()->pluck('class_abilities.id')->toArray();

        $abilities = $allClassAbilities->map(function ($ability) use ($ownedAbilityIds) {
            $ability->is_unlocked = in_array($ability->id, $ownedAbilityIds);
            return $ability;
        });

        return response()->json([
            'success' => true,
            'data' => $abilities
        ]);
    }

    /**
     * Купить (разблокировать) навык
     */
    public function unlock(Request $request, Character $character): JsonResponse
    {
        $request->validate([
            'ability_id' => 'required|exists:class_abilities,id',
        ]);

        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        $ability = ClassAbility::findOrFail($request->ability_id);

        // Проверка класса
        if (mb_strtolower($ability->class) !== mb_strtolower($character->class)) {
            return response()->json(['message' => 'Этот навык не подходит вашему классу'], 400);
        }

        // Проверка уровня
        if ($character->level < $ability->level_required) {
            return response()->json(['message' => "Требуется уровень {$ability->level_required}"], 400);
        }

        // Проверка уже купленного
        if ($character->abilities()->where('ability_id', $ability->id)->exists()) {
            return response()->json(['message' => 'Навык уже изучен'], 400);
        }

        // Проверка золота
        if (!$this->currencyService->hasEnoughGold($character, $ability->gold_cost)) {
            return response()->json(['message' => 'Недостаточно золота'], 400);
        }

        try {
            DB::transaction(function () use ($character, $ability) {
                $this->currencyService->subtractGold($character, $ability->gold_cost);
                $character->abilities()->attach($ability->id);
            });

            return response()->json([
                'success' => true,
                'message' => "Навык \"{$ability->ability_name}\" успешно изучен!",
                'data' => [
                    'character' => $character->fresh(['stats', 'dynamicStats']),
                    'ability' => $ability
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
