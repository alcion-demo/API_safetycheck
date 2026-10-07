<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SafetyCheckController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
    ->only(['index', 'store', 'update'])
    ->middleware('auth:sanctum');