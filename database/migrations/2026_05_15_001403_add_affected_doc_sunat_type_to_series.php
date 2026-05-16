<?php

use App\Enums\Sunat\DocSunatType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->enum('affected_doc_sunat_type', [
                DocSunatType::FACTURA->value,
                DocSunatType::BOLETA->value,
            ])->nullable()->after('doc_sunat_type');;
        });
    }
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn('affected_doc_sunat_type');
        });
    }
};
