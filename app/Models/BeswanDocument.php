<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeswanDocument extends Model
{
    use HasFactory;

    protected $table = 'beswan_documents';

    protected $fillable = [
        'beswan_id',
        'document_type_id', 
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'status',
        'keterangan',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // PERBAIKAN: beswan_id merujuk ke tabel beswans, bukan users
    public function beswan()
    {
        return $this->belongsTo(Beswan::class, 'beswan_id');
    }

    // TAMBAHAN: Relasi langsung ke user melalui beswan
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            Beswan::class,
            'id', // Foreign key di tabel beswans
            'id', // Foreign key di tabel users  
            'beswan_id', // Local key di tabel beswan_documents
            'user_id' // Local key di tabel beswans
        );
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Accessor untuk file URL agar bisa menampilkan URL lengkap
    public function getFileUrlAttribute()
    {
        // Check environment and force production URL for Railway
        $baseUrl = env('APP_ENV') === 'production' || str_contains(env('APP_URL', ''), 'railway')
            ? 'https://web-production-0cc6.up.railway.app'
            : 'http://localhost:8000';

        // If no file path provided, return null
        if (!$this->file_path || $this->file_path === 'null' || $this->file_path === '') {
            return null;
        }

        // If the path is just filename, construct full Laravel storage URL for beswan documents
        if (!str_starts_with($this->file_path, 'http') && !str_starts_with($this->file_path, '/storage')) {
            // Determine folder based on document type
            $documentType = $this->documentType ? $this->documentType->code : 'other';
            $folder = $this->getDocumentFolder($documentType);
            return $baseUrl . '/storage/beswan/' . $folder . '/' . $this->file_path;
        }

        // If the path already starts with /storage, convert to full URL
        if (str_starts_with($this->file_path, '/storage')) {
            return $baseUrl . $this->file_path;
        }

        // If the path already includes the domain, return as is
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }

        // Extract filename from any path structure
        $filename = $this->file_path;
        if (str_contains($filename, '/')) {
            $parts = explode('/', $filename);
            $filename = end($parts) ?: '';
        }

        // Return full Laravel storage URL for beswan documents
        $documentType = $this->documentType ? $this->documentType->code : 'other';
        $folder = $this->getDocumentFolder($documentType);
        return $baseUrl . '/storage/beswan/' . $folder . '/' . $filename;
    }

    // Helper method to determine document folder based on type
    private function getDocumentFolder($documentType)
    {
        $folderMap = [
            'student_proof' => 'wajib',
            'identity_proof' => 'wajib', 
            'photo' => 'wajib',
            'instagram_follow' => 'sosmed',
            'twibbon_post' => 'sosmed',
            'achievement_certificate' => 'pendukung',
            'recommendation_letter' => 'pendukung',
            'essay_motivation' => 'pendukung',
            'cv_resume' => 'pendukung',
            'other_document' => 'pendukung',
        ];

        return $folderMap[$documentType] ?? 'other';
    }
}
