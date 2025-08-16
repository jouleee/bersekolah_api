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
        Schema::create('beasiswa_periods', function (Blueprint $table) {
            $table->id();
            $table->year('tahun');
            $table->string('nama_periode'); // ✅ Tambah yang perlu
            $table->text('deskripsi')->nullable(); // ✅ Tambah yang perlu
            $table->date('mulai_pendaftaran');
            $table->date('akhir_pendaftaran');
            $table->date('mulai_beasiswa');
            $table->date('akhir_beasiswa');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft'); // ✅ Tambah yang perlu
            $table->boolean('is_active')->default(false); // ✅ Tambah yang perlu
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beasiswa_periods');
    }
};