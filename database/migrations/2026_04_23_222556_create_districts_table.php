<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('description');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('province_id')->constrained('provinces');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
