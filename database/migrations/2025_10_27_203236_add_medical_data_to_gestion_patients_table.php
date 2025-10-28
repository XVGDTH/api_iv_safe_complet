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
        Schema::table('gestion_patients', function (Blueprint $table) {
            // ✅ Ajouter les 4 colonnes médicales après la colonne 'serial'
            $table->decimal('temperature', 4, 1)->default(0)->after('serial');
            $table->integer('bpm')->default(0)->after('temperature');
            $table->integer('spo2')->default(0)->after('bpm');
            $table->integer('batterie')->default(0)->after('spo2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestion_patients', function (Blueprint $table) {
            // ✅ Supprimer les colonnes si on fait un rollback
            $table->dropColumn(['temperature', 'bpm', 'spo2', 'batterie']);
        });
    }
};