<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdminBannerController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\AuthController\ForgotPasswordController;
use App\Http\Controllers\AuthController\GetProfileController;
use App\Http\Controllers\AuthController\LoginUserController;
use App\Http\Controllers\AuthController\RegisterUserController;
use App\Http\Controllers\AuthController\VerifyEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AuthController\ResendVerificationCodeController;
use App\Http\Controllers\AuthController\LogoutUserController;
use App\Http\Controllers\AuthController\ResetPasswordController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//CATEGORIAS
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/parent/{parentId?}', [CategoryController::class, 'getByParent']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

//PRODUCTOS
Route::get('/products', [ProductController::class, 'index']);
//Para las subastas
Route::get('/products/on-auction', [ProductController::class, 'onAuction']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('/categories/{categoryId}/products', [ProductController::class, 'getByCategory']);

//AUTH
Route::post('/register', RegisterUserController::class);
Route::post('/login', LoginUserController::class); 
Route::post('/verify-email', VerifyEmailController::class);
//Permite solo 3 peticiones por minuto para esta ruta
Route::post('/resend-code', ResendVerificationCodeController::class)->middleware('throttle:3,1');

//Cualquiera puede ver el estado de la subasta (polling) y el historial
Route::get('/auctions/product/{productId}/status', [AuctionController::class, 'status']);
Route::get('/auctions/product/{productId}/bids', [AuctionController::class, 'bidsHistory']);

//Banner publico
Route::get('/banner', [BannerController::class, 'index']);







// Rutas protegidas, el middleware hace varias cosas
//al recibir la peticion con el ID y el token, va a la db y busca el ID 3
//y comprueba de que el token es válido y no ha expirado, si el token es valido
//busca el id del usuario asociado a ese tokeny y hace una consulta a la db
//para trar el model de Laravel de ese usuario, luego inyecta ese modelo
//de usuario dentro del objeto $request

//Rutas protegidas que requieren que el usuario envíe su token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', GetProfileController::class);
    Route::post('/logout', LogoutUserController::class);
    
    //Carrito de compras
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::delete('/cart/{cartItemId}', [CartController::class, 'destroy']);

    //Para las direcciones
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{id}/default', [AddressController::class, 'setDefault']);

    //Para las ordenes
    Route::post('/orders/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders/mine', [OrderController::class, 'myOrders']);
    Route::post('/auctions/{auctionId}/decline', [AuctionController::class, 'decline']);
    //Ordes del lado del admin
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    //Para poder pujar en las subastas
    Route::post('/auctions/product/{productId}/bid', [AuctionController::class, 'bid']);
    Route::get('/auctions/my-wins', [AuctionController::class, 'myWins']);
    Route::post('/auctions/{auctionId}/checkout', [AuctionController::class, 'checkoutWin']);
    Route::get('/auctions/my-active-bids-count', [AuctionController::class, 'myActiveBidsCount']);
    Route::get('/auctions/my-bid-status', [AuctionController::class, 'myBidStatus']);

    //Solo el admin podra entrar Xd
    Route::middleware('admin')->group(function () {
        Route::get('/orders/all', [OrderController::class, 'allOrders']);
        

        //Para los banners 
        Route::get('/banner-groups', [AdminBannerController::class, 'index']);
        Route::post('/banner-groups', [AdminBannerController::class, 'store']);
        Route::post('/banner-groups/{id}/media', [AdminBannerController::class, 'addMedia']);
        Route::delete('/banner-groups/{groupId}/media/{mediaId}', [AdminBannerController::class, 'removeMedia']);
        Route::patch('/banner-groups/{id}/rename', [AdminBannerController::class, 'rename']);
        Route::delete('/banner-groups/{id}', [AdminBannerController::class, 'destroy']);
        Route::patch('/banner-groups/{id}/toggle-active', [AdminBannerController::class, 'toggleActive']);

        
        //Para las categorias
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{id}', [CategoryController::class, 'update']);
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        //Para los productos
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        Route::patch('/auctions/{auctionId}/extend-duration', [AuctionController::class, 'extendDuration']);
        Route::get('/admin/products', [AdminProductController::class, 'index']);

        //Ruta para el dashboard del admin
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('/dashboard/revenue', [DashboardController::class, 'revenue']);
        Route::get('/dashboard/order-stats', [DashboardController::class, 'orderStats']);
        Route::get('/dashboard/top-categories', [DashboardController::class, 'topCategories']);
        Route::get('/dashboard/revenue-by-sale-type', [DashboardController::class, 'revenueBySaleType']);
        Route::get('/dashboard/export/orders-excel', [DashboardController::class, 'exportOrdersExcel']);
        Route::get('/dashboard/export/sales-excel', [DashboardController::class, 'exportSalesExcel']);
        Route::get('/dashboard/export/sales-pdf', [DashboardController::class, 'exportSalesPdf']);
        Route::get('/dashboard/export/orders-pdf', [DashboardController::class, 'exportOrdersPdf']);
    });

});


// Ruta para solicitar el código de recuperación (Máximo 3 intentos por minuto)
Route::post('/forgot-password', ForgotPasswordController::class)->middleware('throttle:3,1');

//Reuta para cambiar la contraseña xd
Route::post('/reset-password', ResetPasswordController::class)->middleware('throttle:3,1');

