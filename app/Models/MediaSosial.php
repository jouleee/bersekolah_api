<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaSosial extends Model
{
    use HasFactory;

    protected $table = 'media_sosial';
    
    protected $fillable = [
        'twibbon_link',
        'instagram_link',
        'link_grup_beasiswa',
        'whatsapp_number',
        'judul_essay',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];
}
