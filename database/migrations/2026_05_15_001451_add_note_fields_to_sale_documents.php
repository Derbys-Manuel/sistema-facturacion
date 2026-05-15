<?php

use App\Enums\Sunat\CreditNoteReasonType;
use App\Enums\Sunat\DocSunatType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_documents', function (Blueprint $table) {
            $table->foreignUuid('affected_sale_document_id')
                ->nullable()
                ->after('correlative')
                ->constrained('sale_documents');
            $table->enum('affected_doc_sunat_type', [
                DocSunatType::FACTURA->value,
                DocSunatType::BOLETA->value,
            ])
                ->nullable()
                ->after('affected_sale_document_id');
            $table->string('affected_serie', 4)
                ->nullable()
                ->after('affected_doc_sunat_type');
            $table->string('affected_correlative', 10)
                ->nullable()
                ->after('affected_serie');
            $table->enum('note_reason_code',CreditNoteReasonType::values())
                ->nullable()
                ->after('affected_correlative');
            $table->text('note_reason_description')
                ->nullable()
                ->after('note_reason_code');
        });
    }
    public function down(): void
    {
        Schema::table('sale_documents', function (Blueprint $table) {
            $table->dropForeign([
                'affected_sale_document_id'
            ]);
            $table->dropColumn([
                'affected_sale_document_id',
                'affected_doc_sunat_type',
                'affected_serie',
                'affected_correlative',
                'note_reason_code',
                'note_reason_description',
            ]);

        });
    }
};