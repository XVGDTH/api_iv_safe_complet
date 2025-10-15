<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('esp32_id'); // ID de l'ESP32
            $table->decimal('temperature', 5, 2)->nullable(); // Température en °C
            $table->decimal('pression', 8, 2)->nullable(); // Pression en hPa
            $table->boolean('bulle_detectee')->default(false); // Détection de bulles
            $table->decimal('debit_actuel', 8, 2)->default(0); // Débit actuel en mL/h
            $table->decimal('volume_perfuse', 8, 2)->default(0); // Volume perfusé
            $table->integer('batterie_pourcent')->nullable(); // Niveau batterie %
            $table->boolean('pompe_active')->default(false); // État de la pompe
            $table->boolean('vanne_ouverte')->default(false); // État de la vanne
            $table->enum('statut', ['normal', 'alerte', 'critique'])->default('normal');
            $table->text('message_alerte')->nullable();
            $table->timestamp('timestamp_mesure'); // Timestamp de la mesure
            $table->timestamps();

            $table->index(['patient_id', 'timestamp_mesure']);
            $table->index('esp32_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesures');
    }
};
