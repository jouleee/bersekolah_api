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
        Schema::create('beasiswa_applications', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('beswan_id');
            $table->foreignId('beasiswa_period_id')->constrained('beasiswa_periods')->cascadeOnDelete();
            
            $table->enum('status', ['pending', 'lolos_berkas', 'lolos_wawancara', 'diterima', 'ditolak'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->string('interview_link')->nullable();
            // ✅ FIXED: Ubah ke date dan tambah jam mulai
            $table->date('interview_date')->nullable();
            $table->time('interview_time')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            
            $table->timestamps();
            
            $table->foreign('beswan_id')->references('id')->on('beswan')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            
            $table->index(['beswan_id', 'beasiswa_period_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beasiswa_applications');
    }
};
