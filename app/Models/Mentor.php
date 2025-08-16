<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'photo'
    ];

    // Accessor untuk foto mentor agar bisa menampilkan URL lengkap
    public function getPhotoUrlAttribute()
    {
        // Check environment and force production URL for Railway
        $baseUrl = env('APP_ENV') === 'production' || str_contains(env('APP_URL', ''), 'railway')
            ? 'https://web-production-0cc6.up.railway.app'
            : 'http://localhost:8000';

        // If no image path provided, return default
        if (!$this->photo || $this->photo === 'null' || $this->photo === '') {
            return $baseUrl . '/storage/defaults/mentor-default.jpg';
        }

        // If it's already 'default.jpg', return the correct path
        if ($this->photo === 'default.jpg') {
            return $baseUrl . '/storage/defaults/mentor-default.jpg';
        }

        // If the path is just filename, construct full Laravel storage URL
        if (!str_starts_with($this->photo, 'http') && !str_starts_with($this->photo, '/storage')) {
            return $baseUrl . '/storage/admin/mentor/' . $this->photo;
        }

        // If the path already starts with /storage, convert to full URL
        if (str_starts_with($this->photo, '/storage')) {
            return $baseUrl . $this->photo;
        }

        // If the path already includes the domain, return as is
        if (str_starts_with($this->photo, 'http')) {
            return $this->photo;
        }

        // Extract filename from any path structure
        $filename = $this->photo;
        if (str_contains($filename, '/')) {
            $parts = explode('/', $filename);
            $filename = end($parts) ?: 'default.jpg';
        }

        // Return full Laravel storage URL for mentor
        return $baseUrl . '/storage/admin/mentor/' . $filename;
    }
}
