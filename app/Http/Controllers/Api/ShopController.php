<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Item;
use App\Models\Character;
use App\Services\ShopService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(
        protected ShopService $shopService
    ) {}

    public function index()
    {
        return response()->json($this->shopService->getShops());
    }

    public function show(Shop $shop)
    {
        return response()->json($this->shopService->getShop($shop));
    }

    public function buy(Request $request, Shop $shop)
    {
        $request->validate([
            'character_id' => 'required|exists:characters,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'integer|min:1'
        ]);

        $character = Character::findOrFail($request->character_id);
        $item = Item::findOrFail($request->item_id);
        $quantity = $request->input('quantity', 1);

        try {
            $result = $this->shopService->buyItem($character, $shop, $item, $quantity);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
