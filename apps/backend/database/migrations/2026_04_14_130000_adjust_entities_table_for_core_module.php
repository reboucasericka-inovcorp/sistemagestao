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
            if (! Schema::hasColumn('entities', 'type')) {
                $table->enum('type', ['client', 'supplier', 'both'])->default('client')->after('id');
            }

            if (! Schema::hasColumn('entities', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('city')->constrained('countries')->nullOnDelete();
            }
        });

        if (! Schema::hasColumn('entities', 'type')) {
            throw new \Exception('Column type was not created');
        }

        if (Schema::hasColumn('entities', 'is_client') && Schema::hasColumn('entities', 'is_supplier')) {
            DB::table('entities')->update([
                'type' => DB::raw("
                    CASE
                        WHEN is_client = 1 AND is_supplier = 1 THEN 'both'
                        WHEN is_client = 1 THEN 'client'
                        WHEN is_supplier = 1 THEN 'supplier'
                        ELSE 'client'
                    END
                "),
            ]);
        }

        Schema::table('entities', function (Blueprint $table): void {
            try {
                $table->dropIndex('entities_is_client_index');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('entities_is_supplier_index');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('entities_is_client_is_active_index');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('entities_is_supplier_is_active_index');
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('entities', 'country')) {
                $table->dropColumn('country');
            }

            if (Schema::hasColumn('entities', 'is_client')) {
                $table->dropColumn('is_client');
            }

            if (Schema::hasColumn('entities', 'is_supplier')) {
                $table->dropColumn('is_supplier');
            }
        });

        Schema::table('entities', function (Blueprint $table): void {
            $table->index('type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('entities')) {
            return;
        }

        Schema::table('entities', function (Blueprint $table): void {
            if (! Schema::hasColumn('entities', 'country')) {
                $table->string('country', 100)->nullable()->after('city');
            }

            if (! Schema::hasColumn('entities', 'is_client')) {
                $table->boolean('is_client')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('entities', 'is_supplier')) {
                $table->boolean('is_supplier')->default(false)->after('is_client');
            }
        });

        DB::table('entities')->update([
            'is_client' => DB::raw("CASE WHEN type IN ('client', 'both') THEN 1 ELSE 0 END"),
            'is_supplier' => DB::raw("CASE WHEN type IN ('supplier', 'both') THEN 1 ELSE 0 END"),
        ]);

        Schema::table('entities', function (Blueprint $table): void {
            if (Schema::hasColumn('entities', 'country_id')) {
                $table->dropConstrainedForeignId('country_id');
            }

            if (Schema::hasColumn('entities', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
