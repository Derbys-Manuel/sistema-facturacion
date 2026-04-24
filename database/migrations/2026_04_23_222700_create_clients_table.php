<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Sunat\DocIdentityType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->enum('doc_identity_type', DocIdentityType::values());
            $table->string('document_number', 20);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('department_id')->constrained('departments');
            $table->foreignUuid('province_id')->constrained('provinces');
            $table->foreignUuid('district_id')->constrained('districts');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
