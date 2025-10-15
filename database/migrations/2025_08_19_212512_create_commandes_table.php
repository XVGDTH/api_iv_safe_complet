<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('esp32_id'); // ID de l'ESP32 destinataire
            $table->enum('type_commande', [
                'start_perfusion', 'stop_perfusion', 'pause_perfusion',
                'set_debit', 'open_vanne', 'close_vanne',
                'activate_buzzer', 'deactivate_buzzer',
                'led_on', 'led_off', 'emergency_stop'
            ]);
            $table->json('parametres')->nullable(); // Paramètres de la commande
            $table->enum('statut', ['pending', 'sent', 'acknowledged', 'executed', 'failed'])->default('pending');
            $table->timestamp('envoye_at')->nullable();
            $table->timestamp('execute_at')->nullable();
            $table->text('reponse_esp32')->nullable();
            $table->string('envoye_par'); // ID utilisateur ou 'system'
            $table->timestamps();

            $table->index(['esp32_id', 'statut']);
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
