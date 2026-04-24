<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\DocumentType;
use App\Enums\Sunat\DocSunatType;
use App\Enums\Sunat\OperationType;
use App\Enums\Sunat\PaymentForm;



return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('document_type', DocumentType::values());
            $table->string('ubl_version', 5)->nullable();
            $table->enum('doc_sunat_type', DocSunatType::values());
            $table->enum('operation_type', OperationType::values());
            $table->enum('payment_form', PaymentForm::values());
            $table->string('currency')->nullable();
            $table->string('serie',4);
            $table->string('correlative',10);
            $table->integer('credit_days')->nullable();
            $table->smallInteger('num_quota')->nullable();
            $table->decimal('total_taxed', 12, 2)->default(0);
            $table->decimal('total_exempted', 12, 2)->default(0);
            $table->decimal('total_unaffected', 12, 2)->default(0);
            $table->decimal('total_export', 12, 2)->default(0);
            $table->decimal('total_free', 12, 2)->default(0);
            $table->decimal('total_igv', 12, 2)->default(0);
            $table->decimal('total_igv_free', 12, 2)->default(0);
            $table->decimal('icbper', 12, 2)->default(0);
            $table->decimal('total_taxes', 12, 2)->default(0);
            $table->decimal('sale_value', 12, 2)->default(0);
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('total_sale', 12, 2)->default(0);
            $table->decimal('rounding', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->boolean('sunat_state')->nullable();
            $table->string('hash', 255)->nullable();
            $table->longText('xml')->nullable();
            $table->json('cdr')->nullable();
            $table->json('legends')->nullable();
            $table->dateTime('date_issue');
            $table->dateTime('date_expiration');
            $table->text('additional_info')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('company_id')->constrained('companies');
            $table->foreignUuid('client_id')->constrained('clients');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sale_documents');
    }
};
