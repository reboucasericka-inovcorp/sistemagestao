<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            if (! Schema::hasColumn('contacts', 'number')) {
                $table->string('number', 20)->nullable()->after('id');
            }
            if (! Schema::hasColumn('contacts', 'first_name')) {
                $table->string('first_name')->nullable()->after('entity_id');
            }
            if (! Schema::hasColumn('contacts', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('contacts', 'rgpd_consent')) {
                $table->boolean('rgpd_consent')->default(false)->after('email');
            }
            if (Schema::hasColumn('contacts', 'contact_function_id')) {
                $table->foreignId('contact_function_id')->nullable()->change();
            }
        });

        if (Schema::hasColumn('contacts', 'name')) {
            DB::statement("
                UPDATE contacts
                SET
                    first_name = COALESCE(NULLIF(TRIM(SUBSTRING_INDEX(name, ' ', 1)), ''), 'N/A'),
                    last_name = COALESCE(NULLIF(TRIM(SUBSTR(name, LENGTH(SUBSTRING_INDEX(name, ' ', 1)) + 1)), ''), 'N/A')
                WHERE (first_name IS NULL OR first_name = '') OR (last_name IS NULL OR last_name = '')
            ");
        } else {
            DB::table('contacts')
                ->where(function ($query): void {
                    $query->whereNull('first_name')
                        ->orWhere('first_name', '')
                        ->orWhereNull('last_name')
                        ->orWhere('last_name', '');
                })
                ->update([
                    'first_name' => DB::raw("COALESCE(NULLIF(first_name, ''), 'N/A')"),
                    'last_name' => DB::raw("COALESCE(NULLIF(last_name, ''), 'N/A')"),
                ]);
        }

        $contacts = DB::table('contacts')->orderBy('id')->select('id')->get();
        $counter = 1;
        foreach ($contacts as $contact) {
            DB::table('contacts')
                ->where('id', $contact->id)
                ->update(['number' => sprintf('CONT-%04d', $counter++)]);
        }

        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('number', 20)->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->unique('number');

            if (Schema::hasColumn('contacts', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            if (! Schema::hasColumn('contacts', 'name')) {
                $table->string('name')->nullable()->after('contact_function_id');
            }
        });

        DB::statement("
            UPDATE contacts
            SET name = TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))
        ");

        Schema::table('contacts', function (Blueprint $table): void {
            if (Schema::hasColumn('contacts', 'number')) {
                $table->dropUnique(['number']);
                $table->dropColumn('number');
            }
            if (Schema::hasColumn('contacts', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('contacts', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('contacts', 'rgpd_consent')) {
                $table->dropColumn('rgpd_consent');
            }
            if (Schema::hasColumn('contacts', 'contact_function_id')) {
                $table->foreignId('contact_function_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('contacts', 'name')) {
                $table->string('name')->nullable(false)->change();
            }
        });
    }
};
