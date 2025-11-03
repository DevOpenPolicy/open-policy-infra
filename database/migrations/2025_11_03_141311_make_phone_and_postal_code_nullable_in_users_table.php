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
        // Get the database driver to use appropriate SQL syntax
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // For MySQL/MariaDB: Drop unique index, modify column, re-add unique index
            DB::statement('ALTER TABLE users DROP INDEX users_phone_unique');
            DB::statement('ALTER TABLE users MODIFY phone VARCHAR(255) NULL');
            DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_phone_unique (phone)');
            DB::statement('ALTER TABLE users MODIFY postal_code VARCHAR(255) NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY directly, need to recreate table
            // This is more complex, so we'll use a simpler approach
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
            
            DB::statement('ALTER TABLE users ALTER COLUMN phone DROP NOT NULL');
            DB::statement('ALTER TABLE users ALTER COLUMN postal_code DROP NOT NULL');
            
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        } else {
            // For PostgreSQL and others
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
            
            DB::statement('ALTER TABLE users ALTER COLUMN phone DROP NOT NULL');
            DB::statement('ALTER TABLE users ALTER COLUMN postal_code DROP NOT NULL');
            
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE users DROP INDEX users_phone_unique');
            DB::statement('ALTER TABLE users MODIFY phone VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_phone_unique (phone)');
            DB::statement('ALTER TABLE users MODIFY postal_code VARCHAR(255) NOT NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
            
            DB::statement('ALTER TABLE users ALTER COLUMN phone SET NOT NULL');
            DB::statement('ALTER TABLE users ALTER COLUMN postal_code SET NOT NULL');
            
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        }
    }
};
