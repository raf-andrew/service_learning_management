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
        Schema::create('e2ee_user_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('key'); // Encrypted user key (changed from encryption_key)
            $table->string('algorithm', 50)->default('AES-256-GCM'); // Added algorithm column
            $table->unsignedInteger('key_length')->default(32); // Added key_length column
            $table->enum('status', ['active', 'inactive', 'expired', 'revoked', 'rotated'])->default('active'); // Changed from is_active
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('rotated_at')->nullable(); // Added rotated_at column
            $table->timestamp('revoked_at')->nullable(); // Added revoked_at column
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('rotated_at');
            $table->index('revoked_at');

            // Foreign key constraint
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e2ee_user_keys');
    }
}; 