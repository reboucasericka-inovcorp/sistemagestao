<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entities')) {
            return;
        }

        Schema::table('entities', function (Blueprint $table): void {
            if (! Schema::hasColumn('entities', 'is_client')) {
                $table->boolean('is_client')->default(false)->after('gdpr_consent');
            }

            if (! Schema::hasColumn('entities', 'is_supplier')) {
                $table->boolean('is_supplier')->default(false)->after('is_client');
            }
        });

        if (Schema::hasColumn('entities', 'type')) {
            DB::table('entities')->update([
                'is_client' => DB::raw("CASE WHEN type IN ('client', 'both') THEN 1 ELSE is_client END"),
                'is_supplier' => DB::raw("CASE WHEN type IN ('supplier', 'both') THEN 1 ELSE is_supplier END"),
            ]);
        }

        DB::table('entities')
            ->where('is_client', false)
            ->where('is_supplier', false)
            ->update(['is_client' => true]);

        Schema::table('entities', function (Blueprint $table): void {
            if (Schema::hasColumn('entities', 'type')) {
                $table->dropColumn('type');
            }
        });

        $this->ensureIndex('entities', 'entities_is_client_index', ['is_client']);
        $this->ensureIndex('entities', 'entities_is_supplier_index', ['is_supplier']);
        $this->ensureIndex('entities', 'entities_is_client_is_active_index', ['is_client', 'is_active']);
        $this->ensureIndex('entities', 'entities_is_supplier_is_active_index', ['is_supplier', 'is_active']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('entities')) {
            return;
        }

        Schema::table('entities', function (Blueprint $table): void {
            if (! Schema::hasColumn('entities', 'type')) {
                $table->string('type', 20)->default('client')->after('id');
            }
        });

        DB::table('entities')->update([
            'type' => DB::raw("
                CASE
                    WHEN is_client = 1 AND is_supplier = 1 THEN 'both'
                    WHEN is_supplier = 1 THEN 'supplier'
                    ELSE 'client'
                END
            "),
        ]);
    }

    /**
     * @param list<string> $columns
     */
    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement(sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $indexName,
            $table,
            implode(', ', $columns)
        ));
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $databaseName = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(*) AS count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$databaseName, $table, $indexName],
        );

        return (int) ($result[0]->count ?? 0) > 0;
    }
};
