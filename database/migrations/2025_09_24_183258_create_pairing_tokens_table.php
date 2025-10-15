<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pairing_tokens', function (Blueprint $t) {
            $t->uuid('token')->primary();
            $t->foreignId('patient_id')->constrained()->onDelete('cascade');
            $t->timestamp('expires_at');
            $t->timestamp('claimed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairing_tokens');
    }
};
