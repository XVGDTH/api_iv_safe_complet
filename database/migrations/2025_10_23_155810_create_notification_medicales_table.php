<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('notification_medicales', function (Blueprint $table) {
        $table->id();
        $table->string('type')->default('info'); // info / warning / danger
        $table->string('message');
        $table->boolean('is_read')->default(false);
        $table->string('serial')->nullable(); // pour associer au dispositif
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_medicales');
    }
};
