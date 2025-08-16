<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('konten_bersekolah', function (Blueprint $table) {
            $table->id();
            $table->string('judul_halaman');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->string('category')->nullable();
            $table->string('gambar')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('konten_bersekolah');
    }
};
