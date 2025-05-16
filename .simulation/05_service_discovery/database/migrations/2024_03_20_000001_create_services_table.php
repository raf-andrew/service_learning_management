<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('version');
            $table->text('description')->nullable();
            $table->string('status')->default('unknown');
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_health_check')->nullable();
            $table->integer('health_check_interval')->default(60);
            $table->integer('health_check_timeout')->default(5);
            $table->integer('health_check_retries')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
}; 