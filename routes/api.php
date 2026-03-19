<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\WebhookController;

// WhatsApp llama a estas rutas
Route::get ('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);
