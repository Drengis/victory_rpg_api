<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Core\BaseController;
use App\Services\CharacterService;
use App\Models\Character;
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
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255|unique:characters,name',
            'class' => 'required|string|in:Воин,Лучник,Маг',
            'strength' => 'integer|min:1',
            'agility' => 'integer|min:1',
            'constitution' => 'integer|min:1',
            'intelligence' => 'integer|min:1',
            'luck' => 'integer|min:1',
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $relations = ['stats', 'dynamicStats'];
        $paginate = $request->has('paginate')
            ? filter_var($request->input('paginate'), FILTER_VALIDATE_BOOLEAN)
            : true;

        $characters = $this->service->getAll(
            relations: $relations,
            paginate: $paginate,
            filters: ['user_id' => auth()->id()]
        );

        // Обновляем динамические показатели для каждого персонажа (регенерация)
        if ($paginate) {
            foreach ($characters->items() as $character) {
                $this->service->syncStats($character);
                $character->load(['stats', 'dynamicStats']); // Перезагружаем статы для расчета регенерации
                $this->service->refreshDynamicStats($character);
            }
        } else {
            foreach ($characters as $character) {
                // Синхронизируем статы, чтобы max HP/MP были актуальны
                $this->service->syncStats($character);
                $character->load(['stats', 'dynamicStats']); // Перезагружаем статы для расчета регенерации
                // Обновляем динамические показатели
                $this->service->refreshDynamicStats($character);
            }
        }

        return $this->successResponse($characters);
    }

    /**
     * Создать нового персонажа для текущего пользователя
     */
    public function store(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return $this->errorResponse('Необходима авторизация', 401);
        }

        $request->merge(['user_id' => auth()->id()]);
        $response = parent::store($request);
        
        // Подгружаем статы для ответа
        if ($response->getStatusCode() === 201) {
            $data = $response->getData();
            $character = Character::with(['stats', 'dynamicStats'])->find($data->data->id);
            return $this->createdResponse($character);
        }

        return $response;
    }

    /**
     * Переопределяем метод show, чтобы вернуть расчитанные статы
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $character = $this->service->getById($id);
        
        // Сначала пересчитываем основные статы (броня, HP и т.д.)
        $this->service->syncStats($character);
        $character->load(['stats', 'dynamicStats']);
        
        // Затем обновляем динамику (регенерация) на основе новых стат
        $this->service->refreshDynamicStats($character);
        
        // Получаем полные данные для отображения
        $fullData = $this->service->calculateFinalStats($character);
        
        return $this->successResponse(array_merge(
            $character->toArray(),
            ['calculated' => $fullData]
        ));
    }

    /**
     * Распределить очко характеристики
     */
    public function distributeStat(Character $character, Request $request): JsonResponse
    {
        $request->validate([
            'stat' => 'required|string|in:strength,agility,constitution,intelligence,luck',
        ]);

        try {
            $this->service->distributeStatPoint($character, $request->stat);
            
            // Обновляем данные и возвращаем
            $character->refresh();
            $this->service->refreshDynamicStats($character);
            $fullData = $this->service->calculateFinalStats($character);

            return $this->successResponse(array_merge(
                $character->load(['stats', 'dynamicStats'])->toArray(),
                ['calculated' => $fullData]
            ));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
