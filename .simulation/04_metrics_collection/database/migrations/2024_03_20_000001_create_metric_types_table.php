<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->string('data_type');
            $table->json('validation_rules')->nullable();
            $table->json('aggregation_methods')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('data_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_types');
    }
}; 