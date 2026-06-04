<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('prenom', 100);
            $table->string('nom', 100);
            $table->string('telephone', 40);
            // Digits-only form used for dedup so "+33 6 71…", "0033671…" and
            // "0671…" collapse to the same key.
            $table->string('phone_normalized', 20)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['prenom', 'nom', 'phone_normalized'], 'contacts_dedup_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
