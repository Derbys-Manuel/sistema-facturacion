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

        $this->replaceStatusCheck(DocumentStatus::values());
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::table('sale_documents')
            ->where('status', DocumentStatus::WAITING->value)
            ->update(['status' => DocumentStatus::REJECTED->value]);

        $this->replaceStatusCheck([
            DocumentStatus::DRAFT->value,
            DocumentStatus::APPROVED->value,
            DocumentStatus::OBSERVED->value,
            DocumentStatus::REJECTED->value,
        ]);
    }

    /**
     * @param  array<int, string>  $allowedStatuses
     */
    private function replaceStatusCheck(array $allowedStatuses): void
    {
        $allowed = collect($allowedStatuses)
            ->map(fn (string $status): string => "'".str_replace("'", "''", $status)."'")
            ->implode(', ');

        DB::statement(<<<SQL
DO $$
DECLARE
    constraint_name text;
BEGIN
    SELECT conname
    INTO constraint_name
    FROM pg_constraint
    WHERE conrelid = 'sale_documents'::regclass
      AND contype = 'c'
      AND pg_get_constraintdef(oid) LIKE '%status%';

    IF constraint_name IS NOT NULL THEN
        EXECUTE format('ALTER TABLE sale_documents DROP CONSTRAINT %I', constraint_name);
    END IF;

    ALTER TABLE sale_documents
        ADD CONSTRAINT sale_documents_status_check
        CHECK (status IN ({$allowed}));
END $$;
SQL);
    }
};
