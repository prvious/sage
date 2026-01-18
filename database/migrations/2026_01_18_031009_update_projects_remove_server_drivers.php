<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all projects to use artisan driver
        DB::table('projects')->update(['server_driver' => 'artisan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse this - previous driver choice is lost
        // Projects remain as 'artisan'
    }
};
