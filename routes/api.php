<?php

use App\Http\Controllers\CommandController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TicketController;
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
    Route::get('/user/virtual/account', [UserController::class, 'virtual_account']);
    Route::get('/user/balance', [UserController::class, 'balance']);
    Route::post('/user/profile/edit', [UserController::class, 'edit']);
    Route::post('/user/profile/contact', [UserController::class, 'contact']);
    Route::post('/user/profile/security', [UserController::class, 'security']);
    Route::post('/user/new/request', [MessageController::class, 'send']);
    Route::get('/user/requests', [MessageController::class, 'requests']);
    Route::get('/command/{name}', [CommandController::class, 'main']);
    Route::post('/user/new/ticket', [TicketController::class, 'new']);
    Route::post('/user/load/ticket', [TicketController::class, 'load']);
    Route::get('/user/tickets', [TicketController::class, 'tickets']);
    Route::post('/user/topup/paystack', [UserController::class, 'initiate']);
});
Route::get('/verify/paystack', [UserController::class, 'paystack']);