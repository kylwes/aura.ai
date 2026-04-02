<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day'); // ISO: 1=Monday ... 7=Sunday
            $table->time('start');
            $table->time('end');
            $table->timestamps();

            $table->index(['project_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_schedules');
    }
};
