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
        Schema::create('bill_vote_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('bill_url');
            $table->string('session');
            $table->string('description');
            $table->string('result');
            $table->string('vote_url');
            $table->json('vote_json');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_vote_summaries');
    }
};
