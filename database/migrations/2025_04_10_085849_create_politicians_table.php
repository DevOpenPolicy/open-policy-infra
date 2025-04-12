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
        Schema::create('politicians', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('constituency_offices');
            $table->string('email');
            $table->string('voice');
            $table->string('party_name');
            $table->string('party_short_name');
            $table->string('province_name');
            $table->string('province_short_name');
            $table->string('province_location');
            $table->string('politician_url')->unique();
            $table->string('politician_image');
            $table->json('politician_json');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('politicians');
    }
};
