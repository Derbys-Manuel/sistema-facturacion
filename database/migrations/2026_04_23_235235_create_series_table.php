<?php

use App\Enums\Sunat\DocSunatType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('doc_sunat_type', DocSunatType::values());
            $table->string('description')->nullable();
            $table->string('code')->unique();
            $table->string('correlative')->default('00000001');
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('company_id')->constrained('companies');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
