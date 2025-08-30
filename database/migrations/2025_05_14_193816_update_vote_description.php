<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY, so we'll recreate the table
            Schema::table('bill_vote_summaries', function (Blueprint $table) {
                $table->text('description')->change();
            });
        } else {
            DB::statement('ALTER TABLE bill_vote_summaries MODIFY description LONGTEXT');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('bill_vote_summaries', function (Blueprint $table) {
                $table->string('description')->change();
            });
        } else {
            DB::statement('ALTER TABLE bill_vote_summaries MODIFY description VARCHAR(255)');
        }
    }
};
