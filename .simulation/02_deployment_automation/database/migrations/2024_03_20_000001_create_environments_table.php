<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('branch');
            $table->string('url');
            $table->json('variables');
            $table->string('status')->default('ready');
            $table->foreignId('last_deployment_id')->nullable();
            $table->timestamp('last_deployment_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('last_deployment_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environments');
    }
}; 