<?php

use App\Http\Controllers\Api\CharacterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('characters', CharacterController::class);
    Route::post('/characters/{character}/distribute-stat', [CharacterController::class, 'distributeStat']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Combat
    Route::get('/combat/active/{character}', [\App\Http\Controllers\Api\CombatController::class, 'active']);
    Route::post('/combat/start', [\App\Http\Controllers\Api\CombatController::class, 'start']);
    Route::get('/combat/{combat}', [\App\Http\Controllers\Api\CombatController::class, 'show']);
    Route::post('/combat/{combat}/attack', [\App\Http\Controllers\Api\CombatController::class, 'attack']);
    Route::post('/combat/{combat}/defense', [\App\Http\Controllers\Api\CombatController::class, 'defense']);
    Route::post('/combat/{combat}/flee', [\App\Http\Controllers\Api\CombatController::class, 'flee']);

    // Inventory
    Route::get('/inventory/{character}', [\App\Http\Controllers\Api\InventoryController::class, 'index']);
    Route::post('/inventory/equip', [\App\Http\Controllers\Api\InventoryController::class, 'equip']);
    Route::post('/inventory/unequip', [\App\Http\Controllers\Api\InventoryController::class, 'unequip']);
    Route::post('/inventory/sell', [\App\Http\Controllers\Api\InventoryController::class, 'sell']);

    // Enemies
    Route::apiResource('enemies', \App\Http\Controllers\Api\EnemyController::class)->only(['index', 'show']);

    // Shops
    Route::get('/shops', [\App\Http\Controllers\Api\ShopController::class, 'index']);
    Route::get('/shops/{shop}', [\App\Http\Controllers\Api\ShopController::class, 'show']);
    Route::post('/shops/{shop}/buy', [\App\Http\Controllers\Api\ShopController::class, 'buy']);
});
