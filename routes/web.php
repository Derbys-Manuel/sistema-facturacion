<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::view('dashboard', 'dashboard')->name('dashboard');

Route::livewire('/sales', 'pages::sale.index')->name('sales');
