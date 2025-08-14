<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/todos', [TodoController::class, 'index']);
Route::get('/todos/create', [TodoController::class, 'create']);
Route::post('/todos', [TodoController::class, 'store']);
Route::get('/todos/{todo}', [TodoController::class, 'show']);
Route::get('/todos/{todo}/edit', [TodoController::class, 'edit']);
Route::put('/todos/{todo}', [TodoController::class, 'update']);
Route::patch('/todos/{todo}', [TodoController::class, 'update']);
Route::delete('/todos/{todo}', [TodoController::class, 'destroy']);
