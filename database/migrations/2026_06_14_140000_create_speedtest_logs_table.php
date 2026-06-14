<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedtest_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('server_name');
            $table->float('latency_ms');
            $table->float('download_speed'); // in Mbps
            $table->float('upload_speed');   // in Mbps
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedtest_logs');
    }
};
