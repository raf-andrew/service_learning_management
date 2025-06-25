<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sniff_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sniff_result_id')->constrained()->onDelete('cascade');
            $table->string('rule_name');
            $table->string('message');
            $table->string('severity');
            $table->integer('line');
            $table->integer('column');
            $table->boolean('fixable')->default(false);
            $table->boolean('fix_applied')->default(false);
            $table->string('type');
            $table->json('context')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('rule_name');
            $table->index('severity');
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sniff_violations');
    }
}; 