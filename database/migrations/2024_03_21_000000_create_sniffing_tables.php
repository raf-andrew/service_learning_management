<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sniff_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sniff_result_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->integer('line');
            $table->integer('column');
            $table->string('type');
            $table->text('message');
            $table->string('source');
            $table->string('severity');
            $table->boolean('fixable');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['file_path', 'line']);
            $table->index('source');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sniff_violations');
    }
}; 