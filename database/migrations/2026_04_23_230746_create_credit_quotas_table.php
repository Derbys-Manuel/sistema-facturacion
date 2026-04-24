<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\DocumentType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_quotas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('document_type',DocumentType::values());
            $table->integer('number')->nullable();
            $table->date('date_expiration');
            $table->date('date_paid')->nullable();
            $table->decimal('total_to_pay', 10, 2);
            $table->decimal('total_paid', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('sale_document_id')->constrained('sale_documents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_quotas');
    }
};
