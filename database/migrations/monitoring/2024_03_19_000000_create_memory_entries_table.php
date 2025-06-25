<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('memory_entries', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->json('data');
            $table->json('tokens');
            $table->timestamps();
            
            $table->index('category');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('memory_entries');
    }
}; 