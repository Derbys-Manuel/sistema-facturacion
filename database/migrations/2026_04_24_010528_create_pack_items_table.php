<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pack_items', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('pack_id')->constrained('packs');
            $table->foreignUuid('product_id')->constrained('products');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pack_items');
    }
};
