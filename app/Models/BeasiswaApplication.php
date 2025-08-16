<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BeasiswaApplication extends Model
{
    /** @use HasFactory<\Database\Factories\BeasiswaApplicationFactory> */
    use HasFactory;

    protected $table = 'beasiswa_applications';

    protected $fillable = [
        'beswan_id',
        'beasiswa_period_id',
        'status',
        'submitted_at',
        'catatan_admin',
        'interview_link',
        'interview_date',       // ✅ FIXED: date
        'interview_time',       // ✅ FIXED: time
        'finalized_at',
        'reviewed_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'interview_date' => 'date',        // ✅ FIXED: date cast
        'interview_time' => 'datetime:H:i', // ✅ FIXED: time cast
        'finalized_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_LOLOS_BERKAS = 'lolos_berkas';
    const STATUS_LOLOS_WAWANCARA = 'lolos_wawancara';
    const STATUS_DITERIMA = 'diterima';
    const STATUS_DITOLAK = 'ditolak';

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Menunggu Review',
            self::STATUS_LOLOS_BERKAS => 'Lolos Seleksi Berkas',
            self::STATUS_LOLOS_WAWANCARA => 'Lolos Wawancara',
            self::STATUS_DITERIMA => 'Diterima',
            self::STATUS_DITOLAK => 'Ditolak',
        ];
    }

    // ✅ ADDED: Accessor untuk gabungan tanggal dan waktu wawancara
    public function getInterviewDatetimeAttribute()
    {
        if (!$this->interview_date || !$this->interview_time) {
            return null;
        }
        
        return Carbon::createFromFormat('Y-m-d H:i:s', 
            $this->interview_date->format('Y-m-d') . ' ' . $this->interview_time->format('H:i:s')
        );
    }

    // ✅ ADDED: Method untuk format waktu wawancara
    public function getFormattedInterviewTimeAttribute()
    {
        if (!$this->interview_time) {
            return null;
        }
        
        return $this->interview_time->format('H:i');
    }

    // ✅ ADDED: Method untuk end time (asumsi durasi 1 jam)
    public function getInterviewEndTimeAttribute()
    {
        if (!$this->interview_time) {
            return null;
        }
        
        return $this->interview_time->addHour()->format('H:i');
    }

    /**
     * Relasi ke tabel beswans
     */
    public function beswan()
    {
        return $this->belongsTo(Beswan::class, 'beswan_id');
    }

    /**
     * Relasi ke user melalui beswan
     */
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            Beswan::class,
            'id', // Foreign key di tabel beswans
            'id', // Foreign key di tabel users
            'beswan_id', // Local key di tabel beasiswa_applications
            'user_id' // Local key di tabel beswans
        );
    }

    /**
     * Relasi ke periode beasiswa
     */
    public function beasiswaPeriod()
    {
        return $this->belongsTo(BeasiswaPeriods::class, 'beasiswa_period_id');
    }

    /**
     * Relasi ke admin yang review
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope untuk status tertentu
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk periode aktif
     */
    public function scopeActivePeriod($query)
    {
        return $query->whereHas('beasiswaPeriod', function($q) {
            $now = Carbon::now();
            $q->where('mulai_pendaftaran', '<=', $now)
              ->where('akhir_pendaftaran', '>=', $now);
        });
    }

    /**
     * Scope untuk pendaftar berdasarkan user_id
     */
    public function scopeByUser($query, $userId)
    {
        return $query->whereHas('beswan', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * ✅ Get status display text - DITAMBAHKAN
     */
    public function getStatusDisplayAttribute()
    {
        $statusTexts = [
            'pending' => 'Menunggu Review',
            'lolos_berkas' => 'Lolos Berkas',
            'lolos_wawancara' => 'Lolos Wawancara',
            'diterima' => 'Diterima',
            'ditolak' => 'Ditolak'
        ];

        return $statusTexts[$this->status] ?? 'Unknown';
    }

    /**
     * ✅ Get status color - DITAMBAHKAN
     */
    public function getStatusColorAttribute()
    {
        $statusColors = [
            'pending' => 'yellow',
            'lolos_berkas' => 'blue',
            'lolos_wawancara' => 'green',
            'diterima' => 'emerald',
            'ditolak' => 'red'
        ];

        return $statusColors[$this->status] ?? 'gray';
    }

    /**
     * Check apakah aplikasi sudah finalized
     */
    public function isFinalized()
    {
        return !is_null($this->finalized_at);
    }

    /**
     * Check apakah bisa edit
     */
    public function canEdit()
    {
        return !$this->isFinalized() && $this->status === self::STATUS_PENDING;
    }

    /**
     * Check apakah sudah melewati deadline
     */
    public function isPastDeadline()
    {
        if (!$this->beasiswaPeriod) return false;
        
        return Carbon::now()->gt($this->beasiswaPeriod->akhir_pendaftaran);
    }

    /**
     * Get documents untuk aplikasi ini
     */
    public function getDocumentsAttribute()
    {
        if (!$this->beswan) return collect();
        
        return BeswanDocument::where('beswan_id', $this->beswan->id)
            ->with(['documentType'])
            ->get()
            ->groupBy(function($doc) {
                return $doc->documentType->category ?? 'other';
            });
    }

    /**
     * Check kelengkapan dokumen wajib
     */
    public function hasCompleteRequiredDocuments()
    {
        if (!$this->beswan) return false;

        // Get required document types
        $requiredTypes = DocumentType::where('is_required', true)
            ->where('is_active', true)
            ->pluck('code')
            ->toArray();

        // Get user's verified documents
        $verifiedDocs = BeswanDocument::where('beswan_id', $this->beswan->id)
            ->where('status', 'verified')
            ->whereHas('documentType', function($q) use ($requiredTypes) {
                $q->whereIn('code', $requiredTypes);
            })
            ->with('documentType')
            ->get()
            ->pluck('documentType.code')
            ->unique()
            ->toArray();

        // Check if all required types are covered
        return count(array_intersect($requiredTypes, $verifiedDocs)) === count($requiredTypes);
    }

    /**
     * Get verification progress
     */
    public function getVerificationProgress()
    {
        if (!$this->beswan) return 0;

        $requiredTypes = DocumentType::where('is_required', true)
            ->where('is_active', true)
            ->count();

        if ($requiredTypes === 0) return 100;

        $verifiedCount = BeswanDocument::where('beswan_id', $this->beswan->id)
            ->where('status', 'verified')
            ->whereHas('documentType', function($q) {
                $q->where('is_required', true)->where('is_active', true);
            })
            ->distinct('document_type_id')
            ->count();

        return round(($verifiedCount / $requiredTypes) * 100);
    }

    /**
     * Boot method untuk auto-set submitted_at
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (is_null($model->submitted_at)) {
                $model->submitted_at = Carbon::now();
            }
        });
    }
}
