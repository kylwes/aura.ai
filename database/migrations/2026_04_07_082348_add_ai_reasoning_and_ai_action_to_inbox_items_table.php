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
        Schema::table('inbox_items', function (Blueprint $table) {
            $table->string('ai_action')->nullable()->after('ai_suggested_project_id');
            $table->text('ai_reasoning')->nullable()->after('ai_action');
        });
    }

    public function down(): void
    {
        Schema::table('inbox_items', function (Blueprint $table) {
            $table->dropColumn(['ai_action', 'ai_reasoning']);
        });
    }
};
