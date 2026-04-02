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
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->default('UTC');
            $table->string('avatar_url')->nullable();
            $table->time('working_hours_start')->default('09:00');
            $table->time('working_hours_end')->default('17:00');
            $table->json('working_days')->nullable();
            $table->boolean('focus_time_enabled')->default(false);
            $table->time('focus_time_start')->nullable();
            $table->time('focus_time_end')->nullable();
            $table->unsignedInteger('focus_time_min_duration')->default(60);
            $table->unsignedInteger('max_task_duration')->default(120);
            $table->unsignedInteger('buffer_time')->default(15);
            $table->timestamp('onboarded_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'avatar_url',
                'working_hours_start',
                'working_hours_end',
                'working_days',
                'focus_time_enabled',
                'focus_time_start',
                'focus_time_end',
                'focus_time_min_duration',
                'max_task_duration',
                'buffer_time',
                'onboarded_at',
            ]);
        });
    }
};
