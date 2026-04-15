<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->foreignId('country_id')->nullable()->after('city')->constrained('countries')->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('country_id');
            $table->string('mobile', 20)->nullable()->after('phone');
            $table->string('email')->nullable()->after('mobile');
            $table->string('website')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn(['phone', 'mobile', 'email', 'website', 'is_active']);
        });
    }
};
