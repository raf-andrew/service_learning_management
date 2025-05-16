<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('build_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('deployed_by');
            $table->integer('deployment_number');
            $table->foreignId('rollback_to')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('deployment_number');
            $table->index(['environment_id', 'deployment_number']);
            $table->index('started_at');
            $table->index('completed_at');
            $table->index('rollback_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
}; 