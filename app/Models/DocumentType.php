<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $table = 'document_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'is_required',
        'allowed_formats',
        'max_file_size',
        'is_active',
    ];

    protected $casts = [
        'allowed_formats' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function beswanDocuments()
    {
        return $this->hasMany(BeswanDocument::class);
    }
}
