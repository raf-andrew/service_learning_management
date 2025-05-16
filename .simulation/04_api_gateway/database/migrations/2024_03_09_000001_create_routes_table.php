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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->string('method');
            $table->string('target_url');
            $table->string('service_name');
            $table->boolean('is_active')->default(true);
            $table->integer('rate_limit')->default(60);
            $table->integer('timeout')->default(30);
            $table->integer('retry_count')->default(3);
            $table->integer('circuit_breaker_threshold')->default(5);
            $table->integer('circuit_breaker_timeout')->default(60);
            $table->timestamps();

            $table->unique(['path', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
}; 