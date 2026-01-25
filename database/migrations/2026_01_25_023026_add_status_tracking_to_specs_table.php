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
        Schema::table('specs', function (Blueprint $table) {
            $table->string('status')->nullable()->after('generated_from_idea');
            $table->timestamp('processing_started_at')->nullable()->after('status');
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');
            $table->text('error_message')->nullable()->after('processing_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('specs', function (Blueprint $table) {
            $table->dropColumn(['status', 'processing_started_at', 'processing_completed_at', 'error_message']);
        });
    }
};
