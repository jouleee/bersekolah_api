<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Beswan;
use Illuminate\Http\Request;
use App\Models\BeasiswaPeriods;
use Illuminate\Support\Facades\DB;
use App\Models\BeasiswaApplication;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\DocumentType;   // ✅ IMPORT DocumentType
use App\Models\BeswanDocument; // ✅ IMPORT MODEL YANG BENAR

class AdminBeasiswaApplicationController extends Controller
{
    /**
     * Display a listing of beasiswa applications
     */
    public function index(Request $request)
    {
        try {
            $query = BeasiswaApplication::with([
                'beswan.user', 
                'beasiswaPeriod', 
                'reviewer'
            ]);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->whereHas('beswan.user', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                })->orWhereHas('beswan', function($q) use ($search) {
                    $q->where('nama_panggilan', 'LIKE', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by period
            if ($request->has('period') && $request->period !== 'all') {
                $query->where('beasiswa_period_id', $request->period);
            }

            // Filter by finalized only
            if ($request->has('finalized') && $request->finalized == 'true') {
                $query->whereNotNull('finalized_at');
            }

            // ✅ FIXED: Order by untuk MySQL/MariaDB - Manual NULLS LAST
            $query->orderByRaw('finalized_at IS NULL, finalized_at DESC')
                  ->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 15);
            $applications = $query->paginate($perPage);

            // Transform data untuk frontend
            $transformedData = $applications->getCollection()->map(function ($app) {
                // Calculate verification progress
                $verificationProgress = $this->calculateVerificationProgress($app->beswan);
                
                return [
                    'id' => $app->id,
                    'beswan_id' => $app->beswan_id,
                    'user' => [
                        'id' => $app->beswan->user->id,
                        'name' => $app->beswan->user->name,
                        'email' => $app->beswan->user->email,
                    ],
                    'beswan' => [
                        'id' => $app->beswan->id,
                        'nama_panggilan' => $app->beswan->nama_panggilan,
                        'tempat_lahir' => $app->beswan->tempat_lahir,
                        'tanggal_lahir' => $app->beswan->tanggal_lahir,
                        'jenis_kelamin' => $app->beswan->jenis_kelamin,
                    ],
                    'period' => [
                        'id' => $app->beasiswaPeriod->id,
                        'tahun' => $app->beasiswaPeriod->tahun,
                        'nama_periode' => $app->beasiswaPeriod->nama_periode,
                    ],
                    'status' => $app->status,
                    'status_display' => $app->status_display,
                    'status_color' => $app->status_color,
                    'submitted_at' => $app->submitted_at,
                    'finalized_at' => $app->finalized_at,
                    'interview_date' => $app->interview_date,
                    'interview_link' => $app->interview_link,
                    'catatan_admin' => $app->catatan_admin,
                    'reviewer' => $app->reviewer ? [
                        'id' => $app->reviewer->id,
                        'name' => $app->reviewer->name,
                    ] : null,
                    'verification_progress' => $verificationProgress['percentage'],
                    'has_complete_documents' => $verificationProgress['complete'],
                    'created_at' => $app->created_at,
                    'updated_at' => $app->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Applications retrieved successfully',
                'data' => $transformedData,
                'meta' => [
                    'current_page' => $applications->currentPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                    'last_page' => $applications->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching applications: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch applications',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics(Request $request)
    {
        try {
            // Overview statistics
            $overview = [
                'total' => BeasiswaApplication::whereNotNull('finalized_at')->count(),
                'pending' => BeasiswaApplication::where('status', 'pending')->whereNotNull('finalized_at')->count(),
                'lolos_berkas' => BeasiswaApplication::where('status', 'lolos_berkas')->count(),
                'lolos_wawancara' => BeasiswaApplication::where('status', 'lolos_wawancara')->count(),
                'diterima' => BeasiswaApplication::where('status', 'diterima')->count(),
                'ditolak' => BeasiswaApplication::where('status', 'ditolak')->count(),
            ];

            // Weekly applications (last 8 weeks) - Fixed for MySQL
            $weeklyData = BeasiswaApplication::whereNotNull('finalized_at')
                ->where('finalized_at', '>=', Carbon::now()->subWeeks(8))
                ->selectRaw('WEEK(finalized_at) as week, YEAR(finalized_at) as year, COUNT(*) as count')
                ->groupBy('week', 'year')
                ->orderBy('year', 'desc')
                ->orderBy('week', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'week' => "W{$item->week}/{$item->year}",
                        'count' => $item->count
                    ];
                });

            // Available periods
            $periods = BeasiswaPeriods::orderBy('tahun', 'desc')
                ->get(['id', 'tahun', 'nama_periode'])
                ->map(function($period) {
                    return [
                        'id' => $period->id,
                        'tahun' => $period->tahun,
                        'nama_periode' => $period->nama_periode,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => [
                    'overview' => $overview,
                    'weekly' => $weeklyData,
                    'periods' => $periods,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified application
     */
    public function show($id)
    {
        try {
            $application = BeasiswaApplication::with([
                'beswan.user',
                'beswan.keluarga',
                'beswan.alamat',
                'beswan.sekolah',
                'beasiswaPeriod',
                'reviewer'
            ])->findOrFail($id);

            // Get verification progress
            $verificationProgress = $this->calculateVerificationProgress($application->beswan);

            // Get documents
            $documents = $application->beswan->documents()
                ->with('documentType')
                ->get()
                ->groupBy('document_type');

            $transformedData = [
                'id' => $application->id,
                'beswan_id' => $application->beswan_id,
                'user' => [
                    'id' => $application->beswan->user->id,
                    'name' => $application->beswan->user->name,
                    'email' => $application->beswan->user->email,
                    'phone' => $application->beswan->user->phone,
                ],
                'beswan' => [
                    'id' => $application->beswan->id,
                    'nama_panggilan' => $application->beswan->nama_panggilan,
                    'tempat_lahir' => $application->beswan->tempat_lahir,
                    'tanggal_lahir' => $application->beswan->tanggal_lahir,
                    'jenis_kelamin' => $application->beswan->jenis_kelamin,
                    'agama' => $application->beswan->agama,
                ],
                'keluarga' => $application->beswan->keluarga,
                'alamat' => $application->beswan->alamat,
                'sekolah' => $application->beswan->sekolah,
                'period' => [
                    'id' => $application->beasiswaPeriod->id,
                    'tahun' => $application->beasiswaPeriod->tahun,
                    'nama_periode' => $application->beasiswaPeriod->nama_periode,
                ],
                'status' => $application->status,
                'status_display' => $application->status_display,
                'status_color' => $application->status_color,
                'submitted_at' => $application->submitted_at,
                'finalized_at' => $application->finalized_at,
                'interview_date' => $application->interview_date,
                'interview_link' => $application->interview_link,
                'catatan_admin' => $application->catatan_admin,
                'reviewer' => $application->reviewer ? [
                    'id' => $application->reviewer->id,
                    'name' => $application->reviewer->name,
                ] : null,
                'verification_progress' => $verificationProgress,
                'documents' => $documents,
                'created_at' => $application->created_at,
                'updated_at' => $application->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Application detail retrieved successfully',
                'data' => $transformedData
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching application detail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 404);
        }
    }

    /**
     * Update application status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,lolos_berkas,lolos_wawancara,diterima,ditolak',
                'catatan_admin' => 'nullable|string',
                'interview_date' => 'nullable|date',
                'interview_time' => 'nullable|date_format:H:i',
                'interview_link' => 'nullable|url',
            ]);

            $application = BeasiswaApplication::findOrFail($id);
            
            // ✅ FIXED: Validasi khusus untuk status lolos_berkas
            if ($request->status === 'lolos_berkas') {
                $request->validate([
                    'interview_date' => 'required|date',
                    'interview_time' => 'required|date_format:H:i',
                    'interview_link' => 'required|url',
                ], [
                    'interview_date.required' => 'Tanggal wawancara wajib diisi untuk status Lolos Berkas',
                    'interview_time.required' => 'Waktu wawancara wajib diisi untuk status Lolos Berkas',
                    'interview_link.required' => 'Link wawancara wajib diisi untuk status Lolos Berkas',
                ]);
            }

            // Update application
            $application->update([
                'status' => $request->status,
                'catatan_admin' => $request->catatan_admin,
                'interview_date' => $request->interview_date,
                'interview_time' => $request->interview_time,
                'interview_link' => $request->interview_link,
                'reviewed_by' => Auth::id(),
            ]);

            // Load relationships for response
            $application->load(['beswan.user', 'beasiswaPeriod', 'reviewer']);

            return response()->json([
                'success' => true,
                'message' => 'Status aplikasi berhasil diperbarui',
                'data' => $application
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status aplikasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update application statuses
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            $request->validate([
                'application_ids' => 'required|array|min:1',
                'application_ids.*' => 'integer|exists:beasiswa_applications,id',
                'status' => 'required|in:pending,lolos_berkas,lolos_wawancara,diterima,ditolak',
                'catatan_admin' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            $updateData = [
                'status' => $request->status,
                'catatan_admin' => $request->catatan_admin,
                'reviewed_by' => Auth::id(),
                'updated_at' => Carbon::now(),
            ];

            $updatedCount = BeasiswaApplication::whereIn('id', $request->application_ids)
                ->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} applications",
                'data' => [
                    'updated_count' => $updatedCount,
                    'status' => $request->status,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error bulk updating applications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update applications',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Review application (comprehensive review)
     */
    public function reviewApplication(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:lolos_berkas,lolos_wawancara,diterima,ditolak',
                'catatan_admin' => 'required|string|max:1000',
                'interview_date' => 'nullable|date',
                'interview_link' => 'nullable|url|max:500',
            ]);

            DB::beginTransaction();

            $application = BeasiswaApplication::findOrFail($id);

            // Verify that application is finalized
            if (!$application->finalized_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application has not been finalized yet'
                ], 400);
            }

            // Update application with review
            $updateData = [
                'status' => $request->status,
                'catatan_admin' => $request->catatan_admin,
                'reviewed_by' => Auth::id(),
            ];

            if ($request->status === 'lolos_wawancara') {
                $updateData['interview_date'] = $request->interview_date;
                $updateData['interview_link'] = $request->interview_link;
            }

            $application->update($updateData);

            // If status is 'diterima', create beasiswa recipient record
            if ($request->status === 'diterima') {
                $application->beasiswaRecipient()->create([
                    'accepted_at' => Carbon::now()
                ]);
            }

            DB::commit();

            $application->load(['beswan.user', 'beasiswaPeriod', 'reviewer']);

            return response()->json([
                'success' => true,
                'message' => 'Application reviewed successfully',
                'data' => [
                    'id' => $application->id,
                    'status' => $application->status,
                    'status_display' => $application->status_display,
                    'catatan_admin' => $application->catatan_admin,
                    'interview_date' => $application->interview_date,
                    'interview_link' => $application->interview_link,
                    'reviewer' => [
                        'id' => $application->reviewer->id,
                        'name' => $application->reviewer->name,
                    ],
                    'updated_at' => $application->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error reviewing application: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to review application',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Calculate verification progress for a beswan
     */
    private function calculateVerificationProgress($beswan)
    {
        if (!$beswan) {
            return ['percentage' => 0, 'complete' => false, 'verified_count' => 0, 'total_required' => 0];
        }

        try {
            // ✅ SAFE: Cek dokumen terverifikasi melalui relasi DocumentType
            $requiredDocumentCodes = [
                'student_proof', 'identity_proof', 'photo', 'instagram_follow', 'twibbon_post'
            ];

            $verifiedCount = 0;
            $totalRequired = count($requiredDocumentCodes);

            foreach ($requiredDocumentCodes as $docCode) {
                // ✅ Gunakan relasi yang sudah dibuat di model Beswan
                $hasVerifiedDoc = $beswan->documents()
                    ->where('status', 'verified')
                    ->whereHas('documentType', function($q) use ($docCode) {
                        $q->where('code', $docCode);
                    })
                    ->exists();

                if ($hasVerifiedDoc) {
                    $verifiedCount++;
                }
            }

            $percentage = $totalRequired > 0 ? round(($verifiedCount / $totalRequired) * 100) : 0;
            $complete = $verifiedCount === $totalRequired;

            return [
                'percentage' => $percentage,
                'complete' => $complete,
                'verified_count' => $verifiedCount,
                'total_required' => $totalRequired
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating verification progress: ' . $e->getMessage());
            
            // Fallback: return safe default values
            return [
                'percentage' => 0,
                'complete' => false,
                'verified_count' => 0,
                'total_required' => 5
            ];
        }
    }
}