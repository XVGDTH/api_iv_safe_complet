<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('serial')->unique();
            $t->enum('device_type', ['IV_BOX','CUFF']);
            $t->foreignId('patient_id')->nullable()->constrained()->onDelete('cascade');
            $t->string('api_key_hash')->nullable();
            $t->timestamp('last_seen')->nullable();
            $t->enum('status', ['UNASSIGNED','ASSIGNED','BLOCKED'])->default('UNASSIGNED');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
