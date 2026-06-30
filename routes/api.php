<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//CATEGORIAS
Route::get('/categories', [CategoryController::class, 'index']);

Route::post('/categories', [CategoryController::class, 'store']);

Route::put('/categories/{id}', [CategoryController::class, 'update']);

Route::get('/categories/parent/{parentId?}', [CategoryController::class, 'getByParent']);

Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

//PRODUCTOS
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);
Route::get('/categories/{categoryId}/products', [ProductController::class, 'getByCategory']);

