<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropForeign(['vat_id']);
            $table->foreign('vat_id')->references('id')->on('vats')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropForeign(['vat_id']);
            $table->foreign('vat_id')->references('id')->on('vats');
        });
    }
};
