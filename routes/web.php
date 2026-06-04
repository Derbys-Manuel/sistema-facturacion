<?php

use App\Http\Controllers\InvoiceController;
use App\Livewire\Pages\Sale\CreateSaleDocumentPage;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('vouchers'))->name('home');

Route::view('dashboard', 'dashboard')->name('dashboard');

Route::livewire('/boleta', CreateSaleDocumentPage::class)->name('create-boleta');
Route::livewire('/factura', CreateSaleDocumentPage::class)->name('create-factura');
Route::livewire('/nota-credito', CreateSaleDocumentPage::class)->name('create-nota-credito');
Route::livewire('/comprobantes', 'pages::sale.vouchers')->name('vouchers');

Route::get('/comprobantes/{saleId}/pdf', [InvoiceController::class, 'pdf'])->name('sale.pdf');
