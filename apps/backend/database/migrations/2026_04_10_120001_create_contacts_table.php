<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('entity_id')
                ->constrained('entities')
                ->restrictOnDelete();

            $table->foreignId('contact_function_id')
                ->constrained('contact_functions')
                ->restrictOnDelete();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('entity_id');
            $table->index('is_active');
            $table->index(['entity_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
