<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->string('ruc');
            $table->string('urbanization')->nullable();
            $table->string('address')->nullable();
            $table->string('cod_local')->nullable();
            $table->string('sol_user');
            $table->string('sol_pass');
            $table->string('cert_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('production')->default(false);
            $table->string('ubigueo')->nullable(); 
            $table->foreignUuid('department_id')->constrained('departments');
            $table->foreignUuid('province_id')->constrained('provinces');
            $table->foreignUuid('district_id')->constrained('districts');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
