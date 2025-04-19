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
        Schema::table('users', function (Blueprint $table) {
            $table->string('dp')->after('email')->nullable();
            $table->timestamp('deleted_at')->after('remember_token')->nullable();
            $table->longText('account_deletion_reason')->after('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dp');
            $table->dropColumn('deleted_at');
            $table->dropColumn('account_deletion_reason');
        });
    }
};
