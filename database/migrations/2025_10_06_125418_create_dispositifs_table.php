<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dispositifs', function (Blueprint $table) {
            $table->id();
            $table->float('volume_initial')->nullable(); // volume en ml
            $table->float('debit_courant')->nullable();  // débit en ml/min
            $table->integer('batterie')->nullable();     // pourcentage batterie
            $table->integer('temps_restant')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositifs');
    }
};
