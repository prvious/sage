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
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('server_port')->nullable()->after('base_url');
            $table->boolean('tls_enabled')->default(false)->after('server_port');
            $table->string('custom_domain')->nullable()->after('tls_enabled');
            $table->text('custom_directives')->nullable()->after('custom_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['server_port', 'tls_enabled', 'custom_domain', 'custom_directives']);
        });
    }
};
