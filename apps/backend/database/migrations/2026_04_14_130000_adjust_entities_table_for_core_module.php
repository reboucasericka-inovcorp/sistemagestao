<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entities')) {
            return;
        }

        Schema::table('entities', function (Blueprint $table): void {
            if (! Schema::hasColumn('entities', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('city')->constrained('countries')->nullOnDelete();
            }

            if (Schema::hasColumn('entities', 'country')) {
                $table->dropColumn('country');
            }
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

            if (Schema::hasColumn('entities', 'country_id')) {
                $table->dropConstrainedForeignId('country_id');
            }
        });
    }
};
