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
