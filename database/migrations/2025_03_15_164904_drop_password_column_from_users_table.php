<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * This migration removes the password column from the users table
     * to support external authentication systems like WorkOS SSO.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password');
        });


    }

    /**
     * Reverse the migrations.
     *
     * If we need to roll back, we'll add the password column again.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->after('email_verified_at')->nullable();
        });
    }
};
