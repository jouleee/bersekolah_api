<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BerkasCalonBeswan extends Model
{
    /** @use HasFactory<\Database\Factories\BerkasCalonBeswanFactory> */
    use HasFactory;

    protected $fillable = [
        'calon_beswan_id',
        'nama_item',
        'file_path',
        'keterangan',
        'publikasi',
    ];
}
