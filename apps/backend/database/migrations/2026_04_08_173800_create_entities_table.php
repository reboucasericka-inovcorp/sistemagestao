<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table): void {
            $table->id();

            $table->string('number', 50)->unique();
            $table->string('nif', 20)->unique();

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('postal_code', 8)->nullable();
            $table->string('city')->nullable();
            $table->string('country', 100)->nullable();

            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('email')->nullable();

            $table->boolean('is_client')->default(false);
            $table->boolean('is_supplier')->default(false);

            $table->boolean('gdpr_consent')->default(false);
            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('is_client');
            $table->index('is_supplier');
            $table->index('is_active');
            $table->index('name');
            $table->index('nif');
            $table->index(['is_client', 'is_active']);
            $table->index(['is_supplier', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
