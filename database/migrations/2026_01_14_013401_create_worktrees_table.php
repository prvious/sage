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
        Schema::create('worktrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('branch_name');
            $table->string('path')->unique();
            $table->string('preview_url');
            $table->enum('status', ['creating', 'active', 'error', 'cleaning_up', 'deleted'])->default('creating');
            $table->enum('database_isolation', ['separate', 'prefix', 'shared'])->default('separate');
            $table->text('error_message')->nullable();
            $table->json('env_overrides')->nullable();
            $table->timestamps();

            $table->index('branch_name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worktrees');
    }
};
