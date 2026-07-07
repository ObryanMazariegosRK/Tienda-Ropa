<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Cuando alguien visite /login, muestra el archivo login.blade.php
Route::view('/login', 'login')->name('login');

Route::view('/register', 'register')->name('register');

Route::view('/verify', 'verify')->name('verify');

Route::get('/recuperar-password', function () {
    return view('emails.forgot-password'); 
});

Route::get('/cambiar-password', function () {
    return view('emails.reset-password'); 
});