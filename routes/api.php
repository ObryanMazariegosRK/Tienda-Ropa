<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/categories', [CategoryController::class, 'index']);

Route::post('/categories', [CategoryController::class, 'store']);

Route::put('/categories/{id}', [CategoryController::class, 'update']);

Route::get('/categories/parent/{parentId?}', [CategoryController::class, 'getByParent']);

Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

