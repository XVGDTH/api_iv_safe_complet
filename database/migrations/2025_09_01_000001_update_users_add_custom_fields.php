<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'Id')) {
                $table->integer('Id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'prenom')) {
                $table->string('prenom')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'statut')) {
                $table->string('statut')->nullable()->after('telephone');
            }
            if (!Schema::hasColumn('users', 'age')) {
                $table->integer('age')->nullable()->after('statut');
            }
            if (!Schema::hasColumn('users', 'esp32_id')) {
                $table->string('esp32_id')->nullable()->after('age');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'esp32_id')) {
                $table->dropColumn('esp32_id');
            }
            if (Schema::hasColumn('users', 'age')) {
                $table->dropColumn('age');
            }
            if (Schema::hasColumn('users', 'statut')) {
                $table->dropColumn('statut');
            }
            if (Schema::hasColumn('users', 'telephone')) {
                $table->dropColumn('telephone');
            }
            if (Schema::hasColumn('users', 'prenom')) {
                $table->dropColumn('prenom');
            }
            if (Schema::hasColumn('users', 'Id')) {
                $table->dropColumn('Id');
            }
        });
    }
};

