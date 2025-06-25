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
        Schema::create('e2ee_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('transaction_id', 255)->nullable();
            $table->unsignedBigInteger('user_key_id')->nullable();
            $table->string('operation', 50); // encrypt, decrypt, key_generate, etc.
            $table->string('table_name', 100)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('field_name', 100)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            // Indexes
            $table->index('user_id');
            $table->index('transaction_id');
            $table->index('user_key_id');
            $table->index('operation');
            $table->index('table_name');
            $table->index('success');
            $table->index('created_at');
            $table->index('ip_address');

            // Foreign key constraints
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('user_key_id')
                ->references('id')
                ->on('e2ee_user_keys')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e2ee_audit_logs');
    }
}; 