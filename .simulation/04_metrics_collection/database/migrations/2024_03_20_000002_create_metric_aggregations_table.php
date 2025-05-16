<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_aggregations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metric_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('aggregation_method');
            $table->integer('time_window');
            $table->json('group_by')->nullable();
            $table->json('filters')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->index(['metric_type_id', 'name']);
            $table->index('aggregation_method');
            $table->index('time_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_aggregations');
    }
}; 