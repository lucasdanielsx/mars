<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/transactions', [TransactionController::class, 'store']);
Route::post('/v1/users', [UserController::class, 'store']);
