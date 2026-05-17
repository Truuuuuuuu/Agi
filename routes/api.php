<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;


Route::middleware('auth:sanctum')
    ->group(function () {
    
    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files', [FileController::class, 'store']);
    Route::get('/files/{file}/serve', [FileController::class, 'serve']);
    Route::get('/files/{file}', [FileController::class, 'download']);
    Route::delete('/files/{file}', [FileController::class, 'destroy']);
});
