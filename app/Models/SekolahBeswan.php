<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SekolahBeswan extends Model
{
    use HasFactory;

    protected $table = 'sekolah_beswans';

    protected $fillable = [
        'beswan_id',
        'asal_sekolah',
        'daerah_sekolah',
        'jurusan',
        'tingkat_kelas',
    ];

    // Relasi dengan Beswan
    public function beswan()
    {
        return $this->belongsTo(Beswan::class);
    }
}

