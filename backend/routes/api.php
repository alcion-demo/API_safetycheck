<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SafetyCheckController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;

Route::get('/user', function (Request $request) {
    $user = $request->user();

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'roles' => $user->getRoleNames(),
    ]);
})->middleware('auth:sanctum');


Route::apiResource('users', UserController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin']);


Route::patch('/profile', [ProfileController::class, 'update'])
    ->middleware('auth:sanctum');


Route::post('/safety-checks/test', function (Request $request) {
    abort_unless(
        $request->user()->can('safety-check.create'),
        403
    );

    return response()->json([
        'message' => '安全確認を作成する権限があります。',
    ]);
})->middleware('auth:sanctum');

Route::apiResource('safety-checks', SafetyCheckController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware('auth:sanctum');

Route::post(
    'safety-checks/{id}/activate', [SafetyCheckController::class, 'activate'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::post(
    'safety-checks/{id}/close', [SafetyCheckController::class, 'close'])
    ->middleware(['auth:sanctum', 'role:admin']);
