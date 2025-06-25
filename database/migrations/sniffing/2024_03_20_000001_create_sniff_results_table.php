<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sniff_results', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('report_format');
            $table->boolean('fix_applied')->default(false);
            $table->integer('error_count')->default(0);
            $table->integer('warning_count')->default(0);
            $table->timestamp('sniff_date');
            $table->float('execution_time');
            $table->string('phpcs_version');
            $table->json('standards_used');
            $table->string('status');
            $table->json('result_data')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('file_path');
            $table->index('sniff_date');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sniff_results');
    }
}; 