<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::view('dashboard', 'dashboard')->name('dashboard');

Route::livewire('/boleta', 'pages::sale.create-boleta')->name('create-boleta');
Route::livewire('/factura', 'pages::sale.create-factura')->name('create-factura');
Route::livewire('/comprobantes', 'pages::sale.vouchers')->name('vouchers');
