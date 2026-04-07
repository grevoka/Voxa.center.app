<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trunks', function (Blueprint $table) {
            $table->string('outbound_proxy', 255)->nullable()->after('host');
        });
    }

    public function down(): void
    {
        Schema::table('trunks', function (Blueprint $table) {
            $table->dropColumn('outbound_proxy');
        });
    }
};
