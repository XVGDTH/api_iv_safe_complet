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
    //     Schema::table('patients', function (Blueprint $table) {
    //         //
    //     });
    // }
    public function up(): void {
    Schema::table('patients', function (Blueprint $table) {
        $table->string('upid')->unique()->nullable()->after('id');
    });
}
public function down(): void {
    Schema::table('patients', function (Blueprint $table) {
        $table->dropColumn('upid');
    });
}


    /**
     * Reverse the migrations.
      */
    // public function down(): void
    // {
    //     Schema::table('patients', function (Blueprint $table) {
    //         //
    //     });
    // }
};
