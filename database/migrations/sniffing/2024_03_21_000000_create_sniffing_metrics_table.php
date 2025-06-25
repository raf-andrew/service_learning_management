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
        Schema::create('sniffing_metrics', function (Blueprint $table) {
            $table->id();
            $table->float('execution_time');
            $table->bigInteger('memory_usage');
            $table->integer('files_count');
            $table->integer('results_count');
            $table->string('severity');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sniffing_metrics');
    }
}; 