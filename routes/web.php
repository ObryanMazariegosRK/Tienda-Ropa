<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController\SocialAuthController;

// ==========================================
// RUTAS PÚBLICAS Y AUTENTICACIÓN
// ==========================================

Route::get('/', function () {
    return view('welcome');
});

// Cuando alguien visite /login, muestra el archivo login.blade.php
Route::view('/login', 'autenticacion.login')->name('login');
Route::view('/register', 'autenticacion.register')->name('register');
Route::view('/verify', 'autenticacion.verify')->name('verify');

Route::get('/recuperar-password', function () {
    return view('emails.forgot-password'); 
});

Route::get('/cambiar-password', function () {
    return view('emails.reset-password'); 
});

// ==========================================
// AUTENTICACIÓN DEL ADMINISTRADOR
// ==========================================
Route::view('/admin/login', 'admin.login')->name('admin.login');

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


//Para la tiendaaaaaa
Route::view('/ofertas', 'ofertas');

//Política de privacidad (enlazada desde el pie de página; Google la pide para el login)
Route::view('/politica-de-privacidad', 'politica-privacidad')->name('privacidad');


//Para el banner
Route::get('/admin/banners', function () {
    return view('admin.banners.index');
})->name('banners.index');

//Para las ordenes 
Route::view('/mis-pedidos', 'orders');
Route::view('/admin/pedidos', 'admin.pedidos.index')->name('pedidos.index');

//Para las subastas xd
Route::view('/subastas', 'subastas');


//Para el login con google|facebook
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback']);
Route::view('/oauth/callback', 'autenticacion.oauth-callback');

//Para el link de la orden
Route::get('/pedido/{codigo}', function (string $codigo) {
    return view('pedido-redirect', ['codigo' => $codigo]);
});