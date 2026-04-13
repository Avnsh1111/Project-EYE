<?php

use App\Http\Controllers\Api\V2\AuthController;
use App\Http\Controllers\Api\V2\MediaController;
use App\Http\Controllers\Api\V2\ShareLinkController;
use App\Http\Controllers\Api\V2\QuotaController;
use App\Http\Controllers\Api\V2\FamilyController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::get('/share-links/{token}', [ShareLinkController::class, 'show']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Media
    Route::get('/media', [MediaController::class, 'index']);
    Route::get('/media/{id}', [MediaController::class, 'show']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);

    // Quota
    Route::get('/quota', [QuotaController::class, 'show']);

    // Share links
    Route::post('/share-links', [ShareLinkController::class, 'store']);
    Route::delete('/share-links/{token}', [ShareLinkController::class, 'destroy']);

    // Families
    Route::get('/families', [FamilyController::class, 'index']);
    Route::post('/families', [FamilyController::class, 'store']);
    Route::post('/families/{id}/join', [FamilyController::class, 'join']);
    Route::delete('/families/{id}/leave', [FamilyController::class, 'leave']);

    // Sync
    Route::get('/sync/delta', [\App\Http\Controllers\Api\V2\SyncController::class, 'delta']);
    Route::post('/sync/state', [\App\Http\Controllers\Api\V2\SyncController::class, 'upsertState']);
});
