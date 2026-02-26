<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Core\BaseController;
use App\Services\EnemyService;
use Illuminate\Http\Request;

class EnemyController extends BaseController
{
    protected EnemyService $service;

    public function __construct(EnemyService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Enemy::query();

        if ($request->has('dungeon_depth')) {
            $depth = (int) $request->dungeon_depth;
            $query->whereBetween('level', [$depth - 1, $depth + 1]);
        }

        $enemies = $query->get();

        return $this->successResponse($enemies); // Assuming successResponse is available from BaseController
    }

    protected function getService(): EnemyService
    {
        return $this->service;
    }

    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1',
            // Другие поля если нужны для создания мб (хотя обычно враги создаются админами/сидами)
        ];
    }
}
