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
        Schema::create('agent_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('default_agent')->default('claude-code');
            $table->text('claude_code_api_key')->nullable();
            $table->text('opencode_api_key')->nullable();
            $table->timestamp('claude_code_last_tested_at')->nullable();
            $table->timestamp('opencode_last_tested_at')->nullable();
            $table->timestamps();

            $table->unique('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_settings');
    }
};
