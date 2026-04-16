<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_order_id')->constrained('client_orders')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('entities')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('cost_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_order_items');
    }
};
