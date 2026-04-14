<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();

            $table->date('date');
            $table->time('time');



            $table->unsignedInteger('duration');

            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained('entities')->cascadeOnUpdate()->cascadeOnDelete();


            $table->foreignId('type_id')->nullable()->constrained('calendar_types')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('action_id')->nullable()->constrained('calendar_actions')->cascadeOnUpdate()->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('entity_id');
            $table->index('date');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
