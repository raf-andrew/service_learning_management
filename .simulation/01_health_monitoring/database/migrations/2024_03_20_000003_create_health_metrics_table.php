<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // cpu_usage, memory_usage, disk_usage, etc.
            $table->string('type'); // system, service, custom
            $table->float('value');
            $table->string('unit')->nullable(); // %, MB, GB, etc.
            $table->json('labels')->nullable(); // Additional metadata
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['name', 'type', 'recorded_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_metrics');
    }
}; 