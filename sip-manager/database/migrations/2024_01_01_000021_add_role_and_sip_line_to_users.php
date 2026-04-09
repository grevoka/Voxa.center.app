<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'operator'])->default('admin')->after('email');
            $table->foreignId('sip_line_id')->nullable()->after('role')
                  ->constrained('sip_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sip_line_id']);
            $table->dropColumn(['role', 'sip_line_id']);
        });
    }
};
