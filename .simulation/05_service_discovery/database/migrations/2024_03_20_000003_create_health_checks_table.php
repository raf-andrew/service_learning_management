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
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->float('response_time');
            $table->text('error_message')->nullable();
            $table->timestamp('check_time');
            $table->timestamps();

            $table->index(['service_id', 'check_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_checks');
    }
}; 