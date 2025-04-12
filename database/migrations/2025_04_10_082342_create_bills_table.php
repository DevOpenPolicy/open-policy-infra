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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('session');
            $table->timestamp('introduced');
            $table->longText('short_name');
            $table->longText('name');
            $table->string('number');
            $table->string('politician');
            $table->string('bill_url')->unique();
            $table->boolean('is_government_bill');
            $table->json('bills_json');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
