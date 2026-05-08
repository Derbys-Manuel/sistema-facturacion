<?php

use App\Enums\DocumentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE sale_documents DROP CONSTRAINT IF EXISTS sale_documents_status_check');

        $allowedValues = implode("','", DocumentStatus::values());

        DB::statement("ALTER TABLE sale_documents ADD CONSTRAINT sale_documents_status_check CHECK (status IN ('{$allowedValues}'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE sale_documents DROP CONSTRAINT IF EXISTS sale_documents_status_check');

        $allowedValues = implode("','", [
            DocumentStatus::DRAFT->value,
            DocumentStatus::APPROVED->value,
            DocumentStatus::OBSERVED->value,
            DocumentStatus::REJECTED->value,
        ]);

        DB::statement("ALTER TABLE sale_documents ADD CONSTRAINT sale_documents_status_check CHECK (status IN ('{$allowedValues}'))");
    }
};
