<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // threshold, anomaly, status_change
            $table->string('severity'); // info, warning, error, critical
            $table->string('source_type'); // health_check, metric, system
            $table->string('source_id');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'severity']);
            $table->index(['source_type', 'source_id']);
            $table->index('triggered_at');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_alerts');
    }
}; 