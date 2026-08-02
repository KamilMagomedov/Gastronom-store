<?php

use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('webhook', [PaymentController::class, 'webhook'])
    ->name('webhook');
