<?php

use App\Http\Controllers\Api\CharacterController;
use Illuminate\Support\Facades\Route;

Route::apiResource('characters', CharacterController::class);
