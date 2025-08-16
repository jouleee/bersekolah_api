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
        // Check if table exists first
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('application_id');
                $table->enum('document_type', [
                    'student_proof',
                    'identity_proof', 
                    'photo',
                    'instagram_follow',
                    'twibbon_post',
                    'achievement_certificate',
                    'essay_motivation'
                ]);
                $table->string('file_name');
                $table->string('file_path');
                $table->integer('file_size');
                $table->string('mime_type');
                $table->string('original_name')->nullable();
                $table->string('google_drive_file_id')->nullable();
                $table->string('google_drive_link')->nullable();
                $table->timestamps();
                
                // Foreign key constraint
                $table->foreign('application_id')->references('id')->on('beasiswa_applications')->onDelete('cascade');
                
                // Index for better performance
                $table->index(['application_id', 'document_type']);
            });
        } else {
            // If table exists, add missing columns
            Schema::table('documents', function (Blueprint $table) {
                if (!Schema::hasColumn('documents', 'application_id')) {
                    $table->unsignedBigInteger('application_id')->after('id');
                }
                if (!Schema::hasColumn('documents', 'document_type')) {
                    $table->enum('document_type', [
                        'student_proof',
                        'identity_proof', 
                        'photo',
                        'instagram_follow',
                        'twibbon_post',
                        'achievement_certificate',
                        'essay_motivation'
                    ])->after('application_id');
                }
                if (!Schema::hasColumn('documents', 'file_name')) {
                    $table->string('file_name')->after('document_type');
                }
                if (!Schema::hasColumn('documents', 'file_path')) {
                    $table->string('file_path')->after('file_name');
                }
                if (!Schema::hasColumn('documents', 'file_size')) {
                    $table->integer('file_size')->after('file_path');
                }
                if (!Schema::hasColumn('documents', 'mime_type')) {
                    $table->string('mime_type')->after('file_size');
                }
                if (!Schema::hasColumn('documents', 'original_name')) {
                    $table->string('original_name')->nullable()->after('mime_type');
                }
                if (!Schema::hasColumn('documents', 'google_drive_file_id')) {
                    $table->string('google_drive_file_id')->nullable()->after('original_name');
                }
                if (!Schema::hasColumn('documents', 'google_drive_link')) {
                    $table->string('google_drive_link')->nullable()->after('google_drive_file_id');
                }
                
                // Add foreign key if not exists
                if (!Schema::hasColumn('documents', 'application_id')) {
                    $table->foreign('application_id')->references('id')->on('beasiswa_applications')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
