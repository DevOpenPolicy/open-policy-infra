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
        Schema::create('bill_vote_casts', function (Blueprint $table) {
            $table->id();
            $table->string('bill_url');
            $table->string('session');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_supported');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_vote_casts');
    }
};
