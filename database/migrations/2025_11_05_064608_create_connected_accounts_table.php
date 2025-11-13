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
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_user_id')->unique();
            $table->string('access_token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('scope')->nullable();
            $table->string('livemode')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('connected_accounts');
    }
};
