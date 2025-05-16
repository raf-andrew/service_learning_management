<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // http, database, cache, queue, custom
            $table->string('target'); // URL, connection name, etc.
            $table->json('config')->nullable(); // Additional configuration
            $table->integer('timeout')->default(30); // Timeout in seconds
            $table->integer('retry_attempts')->default(3);
            $table->integer('retry_delay')->default(5); // Delay between retries in seconds
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'type']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_checks');
    }
}; 