<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\DocumentType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('document_type', DocumentType::values());
            $table->string('name');
            $table->string('description')->nullable();            
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('company_id')->constrained('companies');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
