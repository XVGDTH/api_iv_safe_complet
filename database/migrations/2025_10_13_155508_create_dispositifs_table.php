<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositifs', function (Blueprint $table) {
            $table->id();
            $table->float('volume_initial')->nullable();
            $table->float('debit_courant')->nullable();
            $table->integer('batterie')->nullable();
            $table->integer('temps_restant')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositifs');
    }
};
