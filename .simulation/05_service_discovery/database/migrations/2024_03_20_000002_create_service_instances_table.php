<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('host');
            $table->integer('port');
            $table->string('status')->default('unknown');
            $table->json('metadata')->nullable();
            $table->timestamp('last_heartbeat')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['service_id', 'host', 'port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_instances');
    }
}; 