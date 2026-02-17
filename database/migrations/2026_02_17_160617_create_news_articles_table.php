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
        Schema::create('news_articles', function ($table) {
            $table->id();
            $table->string('source')->default('g1');
            $table->string('url')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
