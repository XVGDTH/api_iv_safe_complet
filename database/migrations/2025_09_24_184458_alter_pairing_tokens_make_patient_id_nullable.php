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
    Schema::table('pairing_tokens', function (Blueprint $table) {
        $table->foreignId('patient_id')->nullable()->change();
    });
}

public function down()
{
    Schema::table('pairing_tokens', function (Blueprint $table) {
        $table->foreignId('patient_id')->nullable(false)->change();
    });
}



    /**
     * Reverse the migrations.
     */
   
    
};
