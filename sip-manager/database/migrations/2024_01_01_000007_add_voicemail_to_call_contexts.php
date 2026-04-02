<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_contexts', function (Blueprint $table) {
            $table->boolean('voicemail_enabled')->default(false)->after('record_calls');
            $table->string('voicemail_box')->nullable()->after('voicemail_enabled');    // ex: 1001@default
            $table->string('greeting_sound')->nullable()->after('voicemail_box');       // custom greeting file
            $table->string('music_on_hold')->nullable()->after('greeting_sound');       // MOH class
            $table->integer('ring_timeout')->default(25)->after('timeout');             // ring before voicemail
        });
    }

    public function down(): void
    {
        Schema::table('call_contexts', function (Blueprint $table) {
            $table->dropColumn(['voicemail_enabled', 'voicemail_box', 'greeting_sound', 'music_on_hold', 'ring_timeout']);
        });
    }
};
