<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table): void {
            $table->id();

            $table->string('number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            // Mantido supplier_id para compatibilidade com service/requests já implementados.
            $table->foreignId('supplier_id')
                ->constrained('entities')
                ->cascadeOnDelete();

            $table->foreignId('supplier_order_id')
                ->nullable()
                ->constrained('supplier_orders')
                ->nullOnDelete();

            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending_payment', 'paid'])->default('pending_payment');
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('status');
            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
