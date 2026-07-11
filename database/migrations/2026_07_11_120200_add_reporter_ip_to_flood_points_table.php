<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flood_points', function (Blueprint $table) {
            $table->string('reporter_ip')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('flood_points', function (Blueprint $table) {
            $table->dropColumn('reporter_ip');
        });
    }
};
