<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map old status values to new ones
        DB::table('tasks')->where('status', 'idea')->update(['status' => 'queued']);
        DB::table('tasks')->where('status', 'review')->update(['status' => 'waiting_review']);
        // 'in_progress' stays the same
        // 'done' stays the same
        // 'failed' -> map to 'done' (or handle differently if needed)
        DB::table('tasks')->where('status', 'failed')->update(['status' => 'done']);

        // Change enum to string column
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default('queued')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Map new status values back to old ones
        DB::table('tasks')->where('status', 'queued')->update(['status' => 'idea']);
        DB::table('tasks')->where('status', 'waiting_review')->update(['status' => 'review']);

        // Restore the original enum column
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('status', ['idea', 'in_progress', 'review', 'done', 'failed'])
                ->default('idea')
                ->change();
        });
    }
};
