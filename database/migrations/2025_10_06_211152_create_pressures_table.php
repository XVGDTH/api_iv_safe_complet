<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pressures', function (Blueprint $table) {
            $table->id();

            // Champs pour tes mesures IV-SAFE
            $table->double('flow_rate')->nullable();       // débit de perfusion
            $table->double('volume')->nullable();          // volume restant
            $table->integer('battery')->nullable();        // pourcentage batterie
            $table->double('pressure_value')->nullable();  // valeur de pression mesurée
            $table->unsignedBigInteger('patient_id')->nullable(); // si lié à un patient

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pressures');
    }
};
