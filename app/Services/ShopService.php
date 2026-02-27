<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterItem;
use Exception;
use Illuminate\Support\Facades\DB;

class ShopService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Продать предмет из инвентаря
     */
    public function sellItem(Character $character, CharacterItem $charItem, int $quantity = 1): array
    {
        if ($charItem->character_id !== $character->id) {
            throw new Exception("Этот предмет не принадлежит персонажу.");
        }

        if ($charItem->is_equipped) {
            throw new Exception("Нельзя продать экипированный предмет.");
        }

        if ($charItem->quantity < $quantity) {
            throw new Exception("Недостаточно предметов для продажи.");
        }

        if ($quantity <= 0) {
            throw new Exception("Количество должно быть больше нуля.");
        }

        return DB::transaction(function () use ($character, $charItem, $quantity) {
            $item = $charItem->item;

            // Расчет цены продажи (например, 50% от расчетной цены)
            $itemValue = $item->calculatePrice($charItem->ilevel);
            $sellPricePerUnit = (int) floor($itemValue * 0.5);
            $totalGold = $sellPricePerUnit * $quantity;

            // 1. Начисляем золото
            $this->currencyService->addGold($character, $totalGold);

            // 2. Уменьшаем количество или удаляем запись
            if ($charItem->quantity > $quantity) {
                $charItem->quantity -= $quantity;
                $charItem->save();
            } else {
                $charItem->delete();
            }

            return [
                'item_name' => $item->name,
                'quantity_sold' => $quantity,
                'gold_received' => $totalGold,
                'current_gold' => $character->fresh()->gold
            ];
        });
    }

    /**
     * Список всех магазинов
     */
    public function getShops()
    {
        return \App\Models\Shop::all();
    }

    /**
     * Товары конкретного магазина
     */
    public function getShop(\App\Models\Shop $shop)
    {
        return $shop->load('items');
    }

    /**
     * Купить предмет
     */
    public function buyItem(Character $character, \App\Models\Shop $shop, \App\Models\Item $item, int $quantity = 1): array
    {
        $shopItem = $shop->items()->where('item_id', $item->id)->first();

        if (!$shopItem) {
            throw new Exception("Этот товар не продается в данном магазине.");
        }

        $ilevel = $shopItem->pivot->ilevel;
        $pricePerUnit = $shopItem->pivot->price_override ?? $item->calculatePrice($ilevel);
        $totalCost = $pricePerUnit * $quantity;

        if (!$this->currencyService->hasEnoughGold($character, $totalCost)) {
            throw new Exception("Недостаточно золота для покупки.");
        }

        return DB::transaction(function () use ($character, $item, $quantity, $totalCost, $ilevel) {
            // 1. Списываем золото
            $this->currencyService->subtractGold($character, $totalCost);

            // 2. Добавляем в инвентарь
            if (in_array($item->type, ['material', 'junk', 'consumable'])) {
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
                        'quality' => 1,
                        'is_equipped' => false,
                    ]);
                }
            }

            return [
                'item_name' => $item->name,
                'quantity_bought' => $quantity,
                'total_cost' => $totalCost,
                'current_gold' => $character->fresh()->gold
            ];
        });
    }
}
