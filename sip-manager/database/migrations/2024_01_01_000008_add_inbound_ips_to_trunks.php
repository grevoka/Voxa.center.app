<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trunks', function (Blueprint $table) {
            $table->json('inbound_ips')->nullable()->after('context');
            $table->string('inbound_context', 50)->nullable()->after('inbound_ips');
        });
    }

    public function down(): void
    {
        Schema::table('trunks', function (Blueprint $table) {
            $table->dropColumn(['inbound_ips', 'inbound_context']);
        });
    }
};
