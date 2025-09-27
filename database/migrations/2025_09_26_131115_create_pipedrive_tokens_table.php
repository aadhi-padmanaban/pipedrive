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
        Schema::create('pipedrive_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('access_token');
            $table->string('refresh_token');
            $table->timestamp('expires_at'); // when access token expires
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipedrive_tokens');
    }
};
