<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('recurrence_type')->nullable()->after('status'); // daily, weekly, monthly
            $table->json('recurrence_days')->nullable()->after('recurrence_type'); // [1,3,5] for Mon/Wed/Fri
            $table->date('recurrence_end_date')->nullable()->after('recurrence_days');
            $table->foreignId('parent_task_id')->nullable()->after('recurrence_end_date')->constrained('tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_task_id');
            $table->dropColumn(['recurrence_type', 'recurrence_days', 'recurrence_end_date']);
        });
    }
};
