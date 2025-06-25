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
        Schema::create('e2ee_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 255)->unique(); // Unique transaction identifier
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('key_id')->nullable(); // Changed from user_key_id to match model, made nullable
            $table->enum('operation', ['encrypt', 'decrypt']); // Added operation column
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending'); // Updated status enum
            $table->string('algorithm', 50)->nullable(); // Added algorithm column
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp')->useCurrent(); // Added timestamp column
            $table->string('ip_address', 45)->nullable(); // Added IP address column
            $table->text('user_agent')->nullable(); // Added user agent column
            $table->timestamps();

            // Indexes
            $table->index('transaction_id');
            $table->index('user_id');
            $table->index('key_id');
            $table->index('operation');
            $table->index('status');
            $table->index('timestamp');
            $table->index('algorithm');

            // Foreign key constraints
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('key_id')
                ->references('id')
                ->on('e2ee_user_keys')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e2ee_transactions');
    }
}; 