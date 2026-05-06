<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE clients MODIFY address VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE clients ALTER COLUMN address TYPE VARCHAR(255) USING address::text');
        }
        Schema::table('clients', function (Blueprint $table) {
            $table->string('department')->nullable()->after('address');
            $table->string('province')->nullable()->after('department');
            $table->string('district')->nullable()->after('province');
            $table->string('telephone')->nullable()->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['department', 'province', 'district', 'telephone']);
        });
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE clients MODIFY address JSON NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE clients ALTER COLUMN address TYPE JSON USING address::json');
        }
    }
};
