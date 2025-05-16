<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->string('branch');
            $table->string('commit_hash');
            $table->text('commit_message');
            $table->string('status')->default('pending');
            $table->integer('build_number');
            $table->json('artifacts')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('build_number');
            $table->index(['environment_id', 'build_number']);
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builds');
    }
}; 