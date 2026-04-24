<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\DocumentType;

return new class extends Migration
{
 
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('document_type', DocumentType::values());
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->string('note')->nullable();
            $table->foreignUuid('sale_document_id')->constrained('sale_documents');
            $table->foreignUuid('payment_method_id')->constrained('payment_methods');
            $table->foreignUuid('credit_quota_id')->nullable()->constrained('credit_quotas');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
