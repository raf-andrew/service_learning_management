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
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_key_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('request_method');
            $table->string('request_path');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status');
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->float('response_time');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['route_id', 'api_key_id']);
            $table->index('response_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
}; 