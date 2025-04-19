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
        Schema::table('representative_issues', function (Blueprint $table) {
            $table->longText('deletion_reason')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('representative_issues', function (Blueprint $table) {
            $table->dropColumn('deletion_reason');
        });
    }
};
