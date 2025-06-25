<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('github_repositories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('full_name')->unique();
            $table->string('default_branch')->default('main');
            $table->json('settings')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('github_repositories');
    }
}; 