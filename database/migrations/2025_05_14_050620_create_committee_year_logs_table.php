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
        Schema::create('committee_year_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('committee_id');
            $table->string('year')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            // $table->foreignId('committee_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_year_logs');
    }
};
