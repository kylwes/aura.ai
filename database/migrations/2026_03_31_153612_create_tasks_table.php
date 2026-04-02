<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('integration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('priority')->default('medium');
            $table->unsignedInteger('estimated_duration')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->boolean('is_ai_scheduled')->default(false);
            $table->text('ai_reasoning')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'scheduled_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
