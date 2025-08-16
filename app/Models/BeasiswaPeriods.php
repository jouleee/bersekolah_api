<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeasiswaPeriods extends Model
{
    /** @use HasFactory<\Database\Factories\BeasiswaPeriodsFactory> */
    use HasFactory;

    protected $fillable = [
        'tahun',
        'nama_periode',
        'deskripsi',
        'mulai_pendaftaran',
        'akhir_pendaftaran',
        'mulai_beasiswa',
        'akhir_beasiswa',
        'status',
        'is_active',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'mulai_pendaftaran' => 'date',
        'akhir_pendaftaran' => 'date',
        'mulai_beasiswa' => 'date',
        'akhir_beasiswa' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships - Pastikan nama relasi benar
    public function applications()
    {
        // Pastikan foreign key sesuai dengan yang ada di migration BeasiswaApplication
        return $this->hasMany(BeasiswaApplication::class, 'beasiswa_period_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Attributes
    public function getApplicantsCountAttribute()
    {
        return $this->applications()->count();
    }
}
