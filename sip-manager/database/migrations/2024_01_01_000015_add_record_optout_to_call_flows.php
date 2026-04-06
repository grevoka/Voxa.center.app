<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_flows', function (Blueprint $table) {
            $table->boolean('record_optout')->default(false)->after('record_calls');
            $table->char('record_optout_key', 1)->default('8')->after('record_optout');
        });
    }

    public function down(): void
    {
        Schema::table('call_flows', function (Blueprint $table) {
            $table->dropColumn(['record_optout', 'record_optout_key']);
        });
    }
};
