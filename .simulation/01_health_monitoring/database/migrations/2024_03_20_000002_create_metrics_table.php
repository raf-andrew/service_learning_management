<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_health_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->float('value');
            $table->string('unit');
            $table->float('threshold')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index(['service_health_id', 'name', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
}; 