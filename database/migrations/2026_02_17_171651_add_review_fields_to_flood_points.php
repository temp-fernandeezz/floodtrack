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
        Schema::table('flood_points', function ($table) {
            $table->string('source_type')->default('manual');
            $table->string('source_url')->nullable();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->string('review_status')->default('approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flood_points', function (Blueprint $table) {
            
        });
    }
};
