<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->date('proposal_date');
            $table->date('valid_until');
            $table->foreignId('client_id')->constrained('entities')->cascadeOnDelete();
            $table->enum('status', ['draft', 'closed'])->default('draft');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
