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
        Schema::create('sekolah_beswans', function (Blueprint $table) {
        $table->id();
        $table->foreignId('beswan_id')->constrained('beswan')->onDelete('cascade')->primary();
        $table->string('asal_sekolah')->nullable();
        $table->string('daerah_sekolah')->nullable();
        $table->string('jurusan')->nullable();
        $table->string('tingkat_kelas')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sekolah_beswans');
    }
};
