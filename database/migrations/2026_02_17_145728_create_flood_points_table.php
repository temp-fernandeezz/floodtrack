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
        Schema::create('flood_points', function (Blueprint $table) {
            $table->id();

            $table->string('cidade');
            $table->string('bairro')->nullable();
            $table->string('logradouro')->nullable();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('nivel');   // baixo|medio|alto
            $table->string('status');  // ativo|resolvido

            $table->text('descricao')->nullable();
            $table->dateTime('data_ocorrencia')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flood_points');
    }
};
