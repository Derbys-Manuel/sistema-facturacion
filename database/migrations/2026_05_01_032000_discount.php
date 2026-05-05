<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Sunat\DiscountType;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', DiscountType::values());
            $table->string('base_amount');
            $table->string('factor_porcentage');
            $table->string('discount_amount');
            $table->foreignUuid('sale_document_id')->nullable()->constrained('sale_documents');
            $table->foreignUuid('sale_document_item_id')->nullable()->constrained('sale_document_items');
            $table->timestamps();
        });    
    }
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
