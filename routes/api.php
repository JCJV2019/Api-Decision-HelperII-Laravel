<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\PositiveController;
use App\Http\Controllers\API\NegativeController;

Route::controller(RegisterController::class)->group(function () {
    Route::post('users/register', 'register');
    Route::post('users/login', 'login');
});

Route::group(['middleware' => 'auth:sanctum'] ,function() {
    Route::get('users/list', [RegisterController::class, 'userList']);
    Route::delete('users/{id}', [QuestionController::class, 'removeUser']);
    Route::get('pregunta/usuario/{id}', [QuestionController::class, 'questionUser']);
});

Route::middleware('auth:sanctum')->group(function() {
    Route::get('positivos/pregunta/{id}', [PositiveController::class, 'positiveQuestion']);
    Route::delete('positivos/pregunta/{id}', [PositiveController::class, 'removePositiveQuestion']);
    Route::get('negativos/pregunta/{id}', [NegativeController::class, 'negativeQuestion']);
    Route::delete('negativos/pregunta/{id}', [NegativeController::class, 'removeNegativeQuestion']);
});

Route::middleware('auth:sanctum')->group(function() {
    Route::resource('pregunta',QuestionController::class);
    Route::resource('positivos',PositiveController::class);
    Route::resource('negativos',NegativeController::class);
});
