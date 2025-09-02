<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user-profile', [AuthController::class, 'userProfile']);
});

// Routes protégées par l'authentification JWT
Route::middleware('auth:api')->group(function () {
    // Route pour récupérer l'utilisateur connecté
    Route::get('/user', function (Request $request) {
        return $request->user();
    });    
    // Gestion des utilisateurs et des trajets
    Route::apiResources([
        'users' => UserController::class,
        'trips' => TripController::class
    ]);
    
});
