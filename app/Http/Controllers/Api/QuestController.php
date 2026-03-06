<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Core\BaseController;
use App\Models\Quest;
use App\Services\QuestService;
use App\Services\CharacterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestController extends BaseController
{
    protected QuestService $service;
    protected CharacterService $characterService;

    public function __construct(QuestService $service, CharacterService $characterService)
    {
        $this->service = $service;
        $this->characterService = $characterService;
    }

    protected function getService(): QuestService
    {
        return $this->service;
    }

    protected function getValidationRules(): array
    {
        return [
            'character_id' => 'required|exists:characters,id'
        ];
    }

    /**
     * Список квестов персонажа
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id'
        ]);

        $character = $this->characterService->getById($request->character_id);

        if ($character->user_id !== Auth::id()) {
            return $this->errorResponse('Это не ваш персонаж', 403);
        }

        // Синхронизируем прогресс перед показом
        $this->service->updateProgress($character, 'gold_check', 0);
        $this->service->updateProgress($character, 'loot_check', 0);
        
        $quests = $this->service->getQuestsForCharacter($character);

        return $this->successResponse($quests);
    }

    /**
     * Принять квест
     */
    public function accept(Request $request, int $questId): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id'
        ]);

        $character = $this->characterService->getById($request->character_id);

        if ($character->user_id !== Auth::id()) {
            return $this->errorResponse('Это не ваш персонаж', 403);
        }

        try {
            $this->service->acceptQuest($character, $questId);
            return $this->successResponse(['message' => 'Квест принят']);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Забрать награду
     */
    public function claimReward(Request $request, int $questId): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id'
        ]);

        $character = $this->characterService->getById($request->character_id);

        if ($character->user_id !== Auth::id()) {
            return $this->errorResponse('Это не ваш персонаж', 403);
        }

        try {
            $rewards = $this->service->claimReward($character, $questId);
            return $this->successResponse([
                'rewards' => $rewards,
                'message' => 'Награда получена'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
