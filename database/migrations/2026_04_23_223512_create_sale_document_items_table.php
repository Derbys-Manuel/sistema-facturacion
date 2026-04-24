<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('sale_document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();            
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->string('unit',10); 
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_value', 12, 2);           // mtoValorUnitario (sin impuestos)
            $table->decimal('unit_price', 12, 2);           // mtoPrecioUnitario (con impuestos)
            $table->decimal('item_value', 12, 2);           // mtoValorVenta
            $table->unsignedSmallInteger('igv_affectation_type'); // tipAfeIgv
            $table->decimal('igv_base_amount', 12, 2)->default(0); // mtoBaseIgv
            $table->decimal('igv_percent', 5, 2)->default(0);       // porcentajeIgv
            $table->decimal('igv_amount', 12, 2)->default(0);       // igv
            $table->decimal('icbper_factor', 12, 6)->nullable();    // factorIcbper
            $table->decimal('icbper_amount', 12, 2)->default(0);    // icbper
            $table->decimal('taxes_total', 12, 2)->default(0);  
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('sale_document_id')->constrained('sale_documents');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sale_document_items');
    }
};
