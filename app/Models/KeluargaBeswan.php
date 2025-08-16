<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeluargaBeswan extends Model
{
    use HasFactory;

    protected $table = 'keluarga_beswan';
    
    protected $fillable = [
        'beswan_id', 
        'nama_ayah', 
        'pekerjaan_ayah', 
        'penghasilan_ayah',
        'nama_ibu', 
        'pekerjaan_ibu', 
        'penghasilan_ibu', 
        'jumlah_saudara_kandung',
        'jumlah_tanggungan'      
    ];

    public $incrementing = false;
    protected $primaryKey = 'beswan_id';

    // Cast untuk konversi tipe data
    protected $casts = [
        'penghasilan_ayah' => 'string',
        'penghasilan_ibu' => 'string',
        'jumlah_saudara_kandung' => 'string',
        'jumlah_tanggungan' => 'string',
    ];

    // Relasi dengan Beswan
    public function beswan() 
    {
        return $this->belongsTo(Beswan::class);
    }
}