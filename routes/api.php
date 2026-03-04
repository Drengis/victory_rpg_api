<?php

use App\Http\Controllers\Api\CharacterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('characters', CharacterController::class)->only(['index', 'show', 'store']);
    Route::post('/characters/{character}/distribute-stat', [CharacterController::class, 'distributeStat']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Combat
    Route::get('/combat/active/{character}', [\App\Http\Controllers\Api\CombatController::class, 'active']);
    Route::post('/combat/start', [\App\Http\Controllers\Api\CombatController::class, 'start']);
    Route::get('/combat/{combat}', [\App\Http\Controllers\Api\CombatController::class, 'show']);
    Route::post('/combat/{combat}/attack', [\App\Http\Controllers\Api\CombatController::class, 'attack']);
    Route::post('/combat/{combat}/defense', [\App\Http\Controllers\Api\CombatController::class, 'defense']);
    Route::post('/combat/{combat}/ability', [\App\Http\Controllers\Api\CombatController::class, 'useAbility']);
    Route::get('/characters/{character}/abilities', [\App\Http\Controllers\Api\CombatController::class, 'abilities']);
    Route::post('/combat/{combat}/flee', [\App\Http\Controllers\Api\CombatController::class, 'flee']);
    Route::post('/combat/go-deeper', [\App\Http\Controllers\Api\CombatController::class, 'goDeeper']);
    Route::post('/combat/change-depth', [\App\Http\Controllers\Api\CombatController::class, 'changeDepth']);

    // Abilities
    Route::get('/characters/{character}/all-abilities', [\App\Http\Controllers\Api\AbilityController::class, 'index']);
    Route::post('/characters/{character}/unlock-ability', [\App\Http\Controllers\Api\AbilityController::class, 'unlock']);

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

    // Quests
    Route::get('/quests', [\App\Http\Controllers\Api\QuestController::class, 'index']);
    Route::post('/quests/{quest}/accept', [\App\Http\Controllers\Api\QuestController::class, 'accept']);
    Route::post('/quests/{quest}/claim', [\App\Http\Controllers\Api\QuestController::class, 'claimReward']);
});
