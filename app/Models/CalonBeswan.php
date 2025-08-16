<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalonBeswan extends Model
{
    /** @use HasFactory<\Database\Factories\CalonBeswanFactory> */
    use HasFactory;

    protected $table = 'calon_beswans';

    protected $fillable = [
        'user_id',
        'tempat_lahir',
        'tanggal_lahir', 
        'jenis_kelamin',
        'nama_ayah',
        'pekerjaan_ayah',
        'penghasilan_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'penghasilan_ibu',
        'jumlah_saudara',
        'tanggungan_keluarga',
        'alamat',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
