<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sip_lines', function (Blueprint $table) {
            $table->foreignId('outbound_trunk_id')->nullable()->after('context')
                  ->constrained('trunks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sip_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outbound_trunk_id');
        });
    }
};
