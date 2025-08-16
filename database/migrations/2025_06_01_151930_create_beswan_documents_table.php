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
        Schema::create('beswan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beswan_id')->constrained('beswan')->onDelete('cascade');
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 10); // pdf, jpg, png
            $table->integer('file_size');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('keterangan')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Composite unique constraint - satu dokumen type per beswan
            $table->unique(['beswan_id', 'document_type_id']);
            
            // Indexes untuk performance
            $table->index(['beswan_id', 'status']);
            $table->index(['document_type_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beswan_documents');
    }
};
