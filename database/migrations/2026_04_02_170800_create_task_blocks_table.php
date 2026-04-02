<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');
            $table->timestamps();

            $table->index(['task_id', 'scheduled_start']);
            $table->index('scheduled_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_blocks');
    }
};
