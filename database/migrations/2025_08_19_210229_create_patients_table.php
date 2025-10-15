<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('upid')->unique()->nullable();
            $table->string('nom');
            $table->string('prenom');
            $table->string('numero_patient')->unique();
            $table->integer('age')->nullable();
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->text('diagnostic')->nullable();
            $table->string('esp32_id')->unique()->nullable(); // ID de l'ESP32 associé
            $table->boolean('perfusion_active')->default(false);
            $table->timestamp('perfusion_debut')->nullable();
            $table->timestamp('perfusion_fin_prevue')->nullable();
            $table->decimal('debit_ml_h', 8, 2)->default(0); // Débit en mL/h
            $table->decimal('volume_total_ml', 8, 2)->default(0); // Volume total
            $table->decimal('volume_perfuse_ml', 8, 2)->default(0); // Volume déjà perfusé
            $table->timestamps();

            $t->id();
            $t->string('name');
            $t->string('reference')->unique()->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
