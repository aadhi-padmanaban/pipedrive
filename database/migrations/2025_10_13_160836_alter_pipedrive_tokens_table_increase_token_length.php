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
        Schema::table('pipedrive_tokens', function (Blueprint $table) {
            // Change columns to text type (long enough for OAuth tokens)
            $table->text('access_token')->change();
            $table->text('refresh_token')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pipedrive_tokens', function (Blueprint $table) {
            // Revert back to string if needed
            $table->string('access_token', 255)->change();
            $table->string('refresh_token', 255)->change();
        });

    }
};
