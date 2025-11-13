<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::create('mobile_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique(); // random id returned to mobile
            $table->foreignId('connected_account_id')->constrained('connected_accounts')->onDelete('cascade');
            $table->string('session_token')->nullable(); // optional app session token
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('mobile_sessions');
    }
};
