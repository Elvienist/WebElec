<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->enum('role', ['admin', 'student'])->default('student')->after('password');
            $table->unsignedBigInteger('student_id')->nullable()->after('role');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['role', 'student_id']);
        });
    }
};