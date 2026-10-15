<?php

use App\Http\Controllers\Api\V2\PostController;
use Illuminate\Support\Facades\Route;

// Versión 2 (en desarrollo): nuevo formato de respuesta para las publicaciones
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);
