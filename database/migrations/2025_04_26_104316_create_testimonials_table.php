<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('testimoni', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('angkatan_beswan', 20);
            $table->string('sekarang_dimana')->nullable();
            $table->text('isi_testimoni');
            $table->string('foto_testimoni')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->timestamp('tanggal_input')->useCurrent();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
