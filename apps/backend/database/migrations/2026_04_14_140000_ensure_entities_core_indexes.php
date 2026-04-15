<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entities')) {
            return;
        }

        $this->createIndexIfMissing('entities', 'entities_is_client_index', ['is_client']);
        $this->createIndexIfMissing('entities', 'entities_is_supplier_index', ['is_supplier']);
        $this->createIndexIfMissing('entities', 'entities_is_active_index', ['is_active']);
        $this->createIndexIfMissing('entities', 'entities_nif_index', ['nif']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('entities')) {
            return;
        }

        foreach (['entities_is_client_index', 'entities_is_supplier_index', 'entities_is_active_index', 'entities_nif_index'] as $indexName) {
            if ($this->indexExists('entities', $indexName)) {
                DB::statement("DROP INDEX {$indexName}");
            }
        }
    }

    /**
     * @param list<string> $columns
     */
    private function createIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $columnsSql = implode(', ', $columns);
        DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columnsSql})");
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
