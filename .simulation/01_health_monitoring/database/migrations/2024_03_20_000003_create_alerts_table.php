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
            $table->foreignId('service_health_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('level');
            $table->text('message');
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->timestamps();

            $table->index(['service_health_id', 'level', 'resolved']);
            $table->index(['service_health_id', 'acknowledged']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
}; 