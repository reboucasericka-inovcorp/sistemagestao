<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE client_orders MODIFY COLUMN status ENUM('draft','confirmed','closed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE client_orders MODIFY COLUMN status ENUM('draft','confirmed') NOT NULL DEFAULT 'draft'");
    }
};
