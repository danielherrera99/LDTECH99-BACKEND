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
        Schema::create('query_results_cache', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('query');
            $table->json('result_data');
            $table->timestamps();

            $table->unique(['module', 'query']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('query_results_cache');
    }
};
