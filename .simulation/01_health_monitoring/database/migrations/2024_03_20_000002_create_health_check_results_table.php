<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_check_id')->constrained()->onDelete('cascade');
            $table->string('status'); // success, failure, warning
            $table->float('response_time')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['health_check_id', 'checked_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_check_results');
    }
}; 