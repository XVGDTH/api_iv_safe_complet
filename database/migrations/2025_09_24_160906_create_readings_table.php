<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('device_id')->constrained('devices')->onDelete('cascade');
            $t->foreignId('patient_id')->constrained()->onDelete('cascade');
            $t->string('metric'); // ex: temp, hr, rr, sys, dia, spo2, iv_flow, iv_press, iv_vol
            $t->float('value');
            $t->string('unit')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
