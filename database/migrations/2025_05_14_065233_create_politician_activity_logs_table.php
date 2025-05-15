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
        Schema::create('politician_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('politician_id')->unique();
            $table->string('election_summary')->nullable();
            $table->json('activity')->nullable();
            $table->json('latest_activity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('politician_activity_logs');
    }
};
