<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KontenBersekolah extends Model
{
    use HasFactory;

    protected $table = 'konten_bersekolah';
    
    protected $fillable = [
        'judul_halaman',
        'slug',
        'deskripsi',
        'category',
        'gambar',
        'status',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor untuk gambar artikel agar bisa menampilkan URL lengkap
    public function getGambarUrlAttribute()
    {
        // Check environment and force production URL for Railway
        $baseUrl = env('APP_ENV') === 'production' || str_contains(env('APP_URL', ''), 'railway')
            ? 'https://web-production-0cc6.up.railway.app'
            : 'http://localhost:8000';

        // If no image path provided, return default
        if (!$this->gambar || $this->gambar === 'null' || $this->gambar === '') {
            return $baseUrl . '/storage/defaults/artikel-default.jpg';
        }

        // If it's already 'default.jpg', return the correct path
        if ($this->gambar === 'default.jpg') {
            return $baseUrl . '/storage/defaults/artikel-default.jpg';
        }

        // If the path is just filename, construct full Laravel storage URL
        if (!str_starts_with($this->gambar, 'http') && !str_starts_with($this->gambar, '/storage')) {
            return $baseUrl . '/storage/admin/artikel/' . $this->gambar;
        }

        // If the path already starts with /storage, convert to full URL
        if (str_starts_with($this->gambar, '/storage')) {
            return $baseUrl . $this->gambar;
        }

        // If the path already includes the domain, return as is
        if (str_starts_with($this->gambar, 'http')) {
            return $this->gambar;
        }

        // Extract filename from any path structure
        $filename = $this->gambar;
        if (str_contains($filename, '/')) {
            $parts = explode('/', $filename);
            $filename = end($parts) ?: 'default.jpg';
        }

        // Return full Laravel storage URL for artikel
        return $baseUrl . '/storage/admin/artikel/' . $filename;
    }
}
