<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('client_id')->constrained('entities')->cascadeOnDelete();
            $table->date('order_date');
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_orders');
    }
};
