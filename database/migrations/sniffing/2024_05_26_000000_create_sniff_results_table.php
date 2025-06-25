<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSniffResultsTable extends Migration
{
    public function up()
    {
        Schema::create('sniff_results', function (Blueprint $table) {
            $table->id();
            $table->json('result_data');
            $table->string('report_format');
            $table->text('file_path');
            $table->boolean('fix_applied')->default(false);
            $table->integer('error_count')->default(0);
            $table->integer('warning_count')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sniff_results');
    }
}
