<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dispositifs', function (Blueprint $table) {
            $table->integer('temps_restant')->nullable()->after('batterie');
        });
    }

    public function down(): void
    {
        Schema::table('dispositifs', function (Blueprint $table) {
            $table->dropColumn('temps_restant');
        });
    }
};
