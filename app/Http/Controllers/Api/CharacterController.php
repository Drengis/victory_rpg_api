<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Core\BaseController;
use App\Services\CharacterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterController extends BaseController
{
    protected CharacterService $service;

    public function __construct(CharacterService $service)
    {
        $this->service = $service;
    }

    protected function getService(): CharacterService
    {
        return $this->service;
    }

    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'class' => 'required|string|in:Воин,Лучник,Маг',
            'strength' => 'integer|min:1',
            'agility' => 'integer|min:1',
            'constitution' => 'integer|min:1',
            'intelligence' => 'integer|min:1',
            'luck' => 'integer|min:1',
        ];
    }

    /**
     * Переопределяем метод show, чтобы вернуть расчитанные статы
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $character = $this->service->getById($id);
        $fullData = $this->service->calculateFinalStats($character);
        
        return $this->successResponse(array_merge(
            $character->toArray(),
            ['calculated' => $fullData]
        ));
    }
}
