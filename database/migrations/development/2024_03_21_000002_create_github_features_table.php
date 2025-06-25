<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('github_features', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('enabled')->default(false);
            $table->json('conditions')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('github_features');
    }
}; 