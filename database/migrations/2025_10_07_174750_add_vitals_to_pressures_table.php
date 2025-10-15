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
    Schema::table('pressures', function (Blueprint $table) {
        $table->string('serial')->nullable();
        $table->double('temperature')->nullable();
        $table->integer('bpm')->nullable();
        $table->integer('spo2')->nullable();
        $table->integer('batterie')->nullable();
    });
}

public function down(): void
{
    Schema::table('pressures', function (Blueprint $table) {
        $table->dropColumn(['serial','temperature','bpm','spo2','batterie']);
    });
}

};