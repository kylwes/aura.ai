<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbox_items', function (Blueprint $table) {
            $table->foreignId('ai_suggested_project_id')->nullable()->after('ai_estimated_duration')->constrained('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inbox_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_suggested_project_id');
        });
    }
};
