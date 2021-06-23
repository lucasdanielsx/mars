<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('transactions', 'App\Http\Controllers\TransactionController');
Route::apiResource('users', 'App\Http\Controllers\UserController');
