<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('connexions', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->unique();
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('last_data')->nullable();
            $table->boolean('patient_registered')->default(false);
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index('serial');
            $table->index('is_connected');
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connexions');
    }
};