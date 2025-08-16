<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // student_proof, identity_proof, etc
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['wajib', 'pendukung', 'sosial_media']);
            $table->boolean('is_required')->default(true);
            $table->json('allowed_formats')->nullable(); // ['pdf', 'jpg', 'png']
            $table->integer('max_file_size')->default(10485760); // 10MB in bytes
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};