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
        Schema::create('keluarga_beswan', function (Blueprint $table) {
        $table->foreignId('beswan_id')->constrained('beswan')->onDelete('cascade')->primary();
        $table->string('nama_ayah')->nullable();
        $table->string('pekerjaan_ayah')->nullable();
        $table->string('penghasilan_ayah')->nullable();
        $table->string('nama_ibu')->nullable();
        $table->string('pekerjaan_ibu')->nullable();
        $table->string('penghasilan_ibu')->nullable();
        $table->string('jumlah_saudara_kandung')->nullable();
        $table->string('jumlah_tanggungan')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keluarga_beswans');
    }
};
