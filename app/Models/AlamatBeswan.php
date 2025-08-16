<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlamatBeswan extends Model
{
    use HasFactory;

    protected $table = 'alamat_beswans';
    
    protected $fillable = [
        'beswan_id',
        'alamat_lengkap',
        'rt',
        'rw', 
        'kelurahan_desa',
        'kecamatan',
        'kota_kabupaten',
        'provinsi',
        'kode_pos',
        'nomor_telepon',
        'kontak_darurat',
        'email'
    ];

    protected $primaryKey = 'beswan_id';
    public $incrementing = false;

    // Cast untuk konversi tipe data
    protected $casts = [
        'beswan_id' => 'integer',
        'rt' => 'string',
        'rw' => 'string',
        'kode_pos' => 'string',
    ];

    // Relasi dengan Beswan
    public function beswan() 
    {
        return $this->belongsTo(Beswan::class, 'beswan_id', 'id');
    }
}
