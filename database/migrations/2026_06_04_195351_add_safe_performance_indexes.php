<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $this->createIndex(
            'sale_documents_company_state_date_idx',
            'sale_documents (company_id, sunat_state, date_issue DESC)',
        );
        $this->createIndex(
            'sale_documents_company_type_status_date_idx',
            'sale_documents (company_id, doc_sunat_type, status, date_issue DESC)',
        );
        $this->createIndex('sale_documents_client_id_idx', 'sale_documents (client_id)');
        $this->createIndex(
            'sale_documents_affected_sale_document_id_idx',
            'sale_documents (affected_sale_document_id)',
        );
        $this->createIndex(
            'sale_document_items_sale_document_id_idx',
            'sale_document_items (sale_document_id)',
        );
        $this->createIndex(
            'discounts_sale_document_id_idx',
            'discounts (sale_document_id)',
        );
        $this->createIndex(
            'discounts_sale_document_item_id_idx',
            'discounts (sale_document_item_id)',
        );
        $this->createIndex(
            'series_lookup_idx',
            'series (company_id, doc_sunat_type, affected_doc_sunat_type, is_active)',
        );
        $this->createIndex(
            'clients_document_lookup_idx',
            'clients (doc_identity_type, document_number)',
        );
        $this->createIndex('products_active_idx', 'products (is_active)');
    }

    public function down(): void
    {
        foreach ([
            'sale_documents_company_state_date_idx',
            'sale_documents_company_type_status_date_idx',
            'sale_documents_client_id_idx',
            'sale_documents_affected_sale_document_id_idx',
            'sale_document_items_sale_document_id_idx',
            'discounts_sale_document_id_idx',
            'discounts_sale_document_item_id_idx',
            'series_lookup_idx',
            'clients_document_lookup_idx',
            'products_active_idx',
        ] as $index) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$index}");
        }
    }

    private function createIndex(string $name, string $definition): void
    {
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$name} ON {$definition}");
    }
};
