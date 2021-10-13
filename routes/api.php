<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\UserController;
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

Route::post('/auth/login', [UserController::class, 'login']);
Route::post('/auth/register', [UserController::class, 'register']);


Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('/user/logout', [UserController::class, 'logout']);
    Route::get('/user/dashboard', [UserController::class, 'dashboard']);
    Route::get('/user/balance', [UserController::class, 'balance']);
    Route::post('/user/profile/edit', [UserController::class, 'edit']);
    Route::post('/user/profile/contact', [UserController::class, 'contact']);
    Route::post('/user/profile/security', [UserController::class, 'security']);
    Route::get('/user/learning', [PurchaseController::class, 'index']);
});