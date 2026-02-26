<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CharacterItem;
use App\Services\CharacterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    protected CharacterService $characterService;
    protected \App\Services\ShopService $shopService;

    public function __construct(CharacterService $characterService, \App\Services\ShopService $shopService)
    {
        $this->characterService = $characterService;
        $this->shopService = $shopService;
    }

    /**
     * Получить список предметов персонажа
     *
     * @param int $characterId
     * @return JsonResponse
     */
    public function index(int $characterId): JsonResponse
    {
        $character = $this->characterService->getById($characterId);

        if ($character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш персонаж'], 403);
        }

        $items = CharacterItem::with('item')
            ->where('character_id', $characterId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Экипировать предмет
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function equip(Request $request): JsonResponse
    {
        $request->validate([
            'character_item_id' => 'required|exists:character_items,id',
            'slot' => 'required|string|in:weapon,head,helmet,chest,legs,hands,feet,neck,ring,trinket,belt', // Уточнить список слотов
        ]);

        $charItem = CharacterItem::with('character')->findOrFail($request->character_item_id);

        if ($charItem->character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш предмет'], 403);
        }

        try {
            $this->characterService->equipItem($charItem->character, $charItem, $request->slot);
            return response()->json(['success' => true, 'message' => 'Предмет экипирован']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Снять предмет
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unequip(Request $request): JsonResponse
    {
        $request->validate([
            'character_item_id' => 'required|exists:character_items,id',
        ]);

        $charItem = CharacterItem::with('character')->findOrFail($request->character_item_id);

        if ($charItem->character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш предмет'], 403);
        }

        try {
            $this->characterService->unequipItem($charItem->character, $charItem);
            return response()->json(['success' => true, 'message' => 'Предмет снят']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Продать предмет
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sell(Request $request): JsonResponse
    {
        $request->validate([
            'character_item_id' => 'required|exists:character_items,id',
            'quantity' => 'integer|min:1',
        ]);

        $charItem = CharacterItem::with(['character', 'item'])->findOrFail($request->character_item_id);

        if ($charItem->character->user_id !== Auth::id()) {
            return response()->json(['message' => 'Это не ваш предмет'], 403);
        }

        try {
            $result = $this->shopService->sellItem(
                $charItem->character, 
                $charItem, 
                $request->input('quantity', 1)
            );
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
