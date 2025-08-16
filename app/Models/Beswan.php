<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beswan extends Model
{
    use HasFactory;

    protected $table = 'beswan';

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nama_panggilan',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi dengan KeluargaBeswan
    public function keluargaBeswan()
    {
        return $this->hasOne(KeluargaBeswan::class);
    }

    // ✅ ALIAS untuk keluarga (sesuai dengan yang dipakai di controller admin)
    public function keluarga()
    {
        return $this->hasOne(KeluargaBeswan::class);
    }

    // Relasi dengan SekolahBeswan
    public function sekolahBeswan()
    {
        return $this->hasOne(SekolahBeswan::class);
    }

    // ✅ ALIAS untuk sekolah (sesuai dengan yang dipakai di controller admin)
    public function sekolah()
    {
        return $this->hasOne(SekolahBeswan::class);
    }

    // Relasi dengan AlamatBeswan
    public function alamatBeswan()
    {
        return $this->hasOne(AlamatBeswan::class);
    }

    // ✅ ALIAS untuk alamat (sesuai dengan yang dipakai di controller admin)
    public function alamat()
    {
        return $this->hasOne(AlamatBeswan::class);
    }

    // ✅ TAMBAHKAN: Relasi dengan BeswanDocument (nama model yang benar)
    public function documents()
    {
        return $this->hasMany(BeswanDocument::class);
    }

    // ✅ TAMBAHKAN: Relasi dengan BeasiswaApplication
    public function beasiswaApplications()
    {
        return $this->hasMany(BeasiswaApplication::class);
    }

    // Get latest beasiswa application
    public function latestBeasiswaApplication()
    {
        return $this->hasOne(BeasiswaApplication::class)->latest();
    }

    // Get nama_lengkap with fallback to user name
    public function getNamaLengkapAttribute($value)
    {
        if ($value) {
            return $value;
        }
        
        if ($this->user && $this->user->name) {
            return $this->user->name;
        }
        
        return $this->nama_panggilan ?? '-';
    }

    // Check if beswan has verified documents
    public function hasVerifiedDocuments()
    {
        return $this->documents()->where('status', 'verified')->exists();
    }

    // Get verification progress percentage
    public function getVerificationProgressAttribute()
    {
        $requiredDocumentTypes = [
            'student_proof', 'identity_proof', 'photo', 
            'instagram_follow', 'twibbon_post'
        ];

        $verifiedCount = 0;
        $totalRequired = count($requiredDocumentTypes);

        foreach ($requiredDocumentTypes as $docType) {
            $hasVerifiedDoc = $this->documents()
                ->where('document_types', $docType)
                ->where('status', 'verified')
                ->exists();

            if ($hasVerifiedDoc) {
                $verifiedCount++;
            }
        }

        return $totalRequired > 0 ? round(($verifiedCount / $totalRequired) * 100) : 0;
    }
}
