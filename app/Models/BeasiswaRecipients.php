<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeasiswaRecipients extends Model
{
    /** @use HasFactory<\Database\Factories\BeasiswaRecipientsFactory> */
    use HasFactory;

    protected $fillable = [
        'beasiswa_application_id',
        'accepted_at',
    ];
}
