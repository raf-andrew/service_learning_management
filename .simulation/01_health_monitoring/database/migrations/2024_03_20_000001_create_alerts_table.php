<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->enum('severity', ['info', 'warning', 'critical']);
            $table->text('message');
            $table->string('status');
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'severity']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
}; 