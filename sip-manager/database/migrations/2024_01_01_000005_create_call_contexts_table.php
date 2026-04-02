<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();          // ex: from-internal, from-trunk, outbound
            $table->enum('direction', ['inbound', 'outbound', 'internal']);
            $table->string('description')->nullable();
            $table->text('dial_pattern')->nullable();       // ex: _0XXXXXXXXX, _+33XXXXXXXXX, _X.
            $table->string('destination')->nullable();      // trunk name or extension range
            $table->string('destination_type')->default('extensions'); // extensions, trunk, ivr, queue
            $table->string('trunk_id')->nullable();         // trunk used for outbound
            $table->string('caller_id_override')->nullable();
            $table->string('prefix_strip')->nullable();     // digits to strip before dialing
            $table->string('prefix_add')->nullable();       // digits to prepend
            $table->integer('timeout')->default(30);
            $table->boolean('record_calls')->default(false);
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(10);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_contexts');
    }
};
