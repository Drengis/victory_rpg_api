<?php

namespace App\Services;

use App\Models\Character;
use Exception;

class CurrencyService
{
    /**
     * Добавить золото персонажу
     */
    public function addGold(Character $character, int $amount): void
    {
        if ($amount < 0) {
            throw new Exception("Сумма добавления не может быть отрицательной.");
        }

        $character->gold += $amount;
        $character->save();
    }

    /**
     * Списать золото у персонажа
     */
    public function subtractGold(Character $character, int $amount): void
    {
        if ($amount < 0) {
            throw new Exception("Сумма списания не может быть отрицательной.");
        }

        if (!$this->hasEnoughGold($character, $amount)) {
            throw new Exception("Недостаточно золота.");
        }

        $character->gold -= $amount;
        $character->save();
    }

    /**
     * Проверить достаточность золота
     */
    public function hasEnoughGold(Character $character, int $amount): bool
    {
        return $character->gold >= $amount;
    }
}
