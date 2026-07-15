<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// RUTAS PÚBLICAS Y AUTENTICACIÓN
// ==========================================

Route::get('/', function () {
    return view('welcome');
});

// Cuando alguien visite /login, muestra el archivo login.blade.php
Route::view('/login', 'login')->name('login');
Route::view('/register', 'register')->name('register');
Route::view('/verify', 'verify')->name('verify');

Route::get('/recuperar-password', function () {
    return view('emails.forgot-password'); 
});

Route::get('/cambiar-password', function () {
    return view('emails.reset-password'); 
});


// ==========================================
// RUTAS DEL PANEL DE ADMINISTRACIÓN
// ==========================================

// 1. Dashboard Principal
Route::view('/admin/dashboard', 'admin.dashboard')->name('dashboard');

// 2. Módulo de Categorías (Listado)
Route::view('/admin/categorias', 'admin.categorias.index')->name('categorias.index');

// 3. Módulo de Productos (Catálogo principal - Grid de tarjetas)
// URL: misitio.com/admin/productos
Route::view('/admin/productos/catalogo', 'admin.productos.catalogo')->name('productos.catalogo');

// 4. Módulo de Productos (Formulario para crear un producto)
// URL: misitio.com/admin/productos/crear
Route::view('/admin/productos/crear', 'admin.productos.index')->name('productos.index');