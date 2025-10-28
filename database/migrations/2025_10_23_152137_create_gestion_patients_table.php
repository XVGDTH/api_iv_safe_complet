<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('gestion_patients', function (Blueprint $table) {
    //         $table->id();
    //         $table->timestamps();
    //     });
    // }

    public function up()
{
    Schema::create('gestion_patients', function (Blueprint $table) {
        $table->id();
        $table->string('nom');
        $table->string('prenom');
        $table->integer('age');
        $table->float('poids');
        $table->string('telephone');
        $table->string('serial')->nullable(); // numéro du dispositif IV-SAFE
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestion_patients');
    }
};
