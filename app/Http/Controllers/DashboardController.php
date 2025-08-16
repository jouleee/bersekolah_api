<?php

namespace App\Http\Controllers;

use App\Models\BeasiswaApplication;
use App\Models\BeasiswaRecipients;
use App\Models\Beswan;
use App\Models\BerkasCalonBeswan;
use App\Models\BeswanDocument;
use App\Models\CalonBeswan;
use App\Models\Mentor;
use App\Models\BeasiswaPeriods;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get quick action statistics for dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */    public function getQuickActionStats()
    {
        try {
            // Get total applicants count - using BeasiswaApplication model instead of CalonBeswan
            $total_pendaftar = BeasiswaApplication::distinct('beswan_id')->count('beswan_id');
            
            // Get total active beswan count
            $total_beswan = BeasiswaRecipients::count();
            
            // Get total mentors count
            $total_mentor = Mentor::count();
            
            // Get total document uploads count
            $total_dokumen = BeswanDocument::count();
            
            return response()->json([
                'total_pendaftar' => $total_pendaftar,
                'total_beswan' => $total_beswan,
                'total_mentor' => $total_mentor,
                'total_dokumen' => $total_dokumen
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching dashboard stats', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent activities for dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */    public function getRecentActivities()
    {
        try {
            // Get recent applications (last 7 days)
            $recentApplications = BeasiswaApplication::with('user:id,name')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'application',
                        'title' => 'Pendaftaran Beasiswa Baru',
                        'user' => $item->user ? $item->user->name : 'User',
                        'date' => $item->created_at,
                        'status' => $item->status
                    ];
                });
                
            // Recent document uploads (last 7 days)
            $recentDocuments = BerkasCalonBeswan::with(['user:id,name', 'documentType:id,name'])
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'document',
                        'title' => 'Dokumen Baru: ' . ($item->documentType ? $item->documentType->name : 'Dokumen'),
                        'user' => $item->user ? $item->user->name : 'User',
                        'date' => $item->created_at,
                        'status' => $item->status
                    ];
                });
                
            // Merge and sort by date
            $activities = $recentApplications->concat($recentDocuments)
                ->sortByDesc('date')
                ->values()
                ->take(10);
                
            return response()->json([
                'data' => $activities
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching recent activities', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get application statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApplicationStats()
    {
        try {
            // Get total applications by status using the correct status enum values from the model
            $total = BeasiswaApplication::count();
            $pending = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_PENDING)->count();
            $approved = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_DITERIMA)->count();
            $rejected = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_DITOLAK)->count();
            
            // Also include additional status counts that might be useful
            $lolos_berkas = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_LOLOS_BERKAS)->count();
            $lolos_wawancara = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_LOLOS_WAWANCARA)->count();
            
            // Get applications by period - use the correct field name
            $by_period = BeasiswaApplication::select('beasiswa_period_id', DB::raw('COUNT(*) as count'))
                ->groupBy('beasiswa_period_id')
                ->with('beasiswaPeriod:id,nama_periode,tahun')
                ->get()
                ->map(function ($item) {
                    return [
                        'period_id' => $item->beasiswa_period_id,
                        'period_name' => $item->beasiswaPeriod ? 
                            $item->beasiswaPeriod->nama_periode . ' ' . $item->beasiswaPeriod->tahun : 
                            'Unknown Period',
                        'count' => $item->count
                    ];
                });
                
            return response()->json([
                'total' => $total,
                'pending' => $pending,
                'diterima' => $approved, // Use the model's field name for consistency
                'ditolak' => $rejected,  // Use the model's field name for consistency
                'lolos_berkas' => $lolos_berkas,
                'lolos_wawancara' => $lolos_wawancara,
                'by_period' => $by_period
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching application stats', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get period statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPeriodStats()
    {
        try {
            $currentPeriods = BeasiswaPeriods::where('end_date', '>=', Carbon::now())
                ->withCount(['applications as total_applicants'])
                ->orderBy('start_date', 'desc')
                ->take(3)
                ->get()
                ->map(function ($period) {
                    // Get approval rates
                    $totalApplications = $period->total_applicants;
                    $approved = BeasiswaApplication::where('beasiswa_periods_id', $period->id)
                        ->where('status', 'approved')
                        ->count();
                    
                    $approvalRate = $totalApplications > 0 ? round(($approved / $totalApplications) * 100) : 0;
                    
                    // Calculate days remaining
                    $daysRemaining = Carbon::now()->diffInDays(Carbon::parse($period->end_date), false);
                    $daysRemaining = max(0, $daysRemaining); // Ensure it's not negative
                    
                    // Calculate progress percentage
                    $totalDays = Carbon::parse($period->start_date)->diffInDays(Carbon::parse($period->end_date));
                    $daysPassed = $totalDays - $daysRemaining;
                    $progress = $totalDays > 0 ? round(($daysPassed / $totalDays) * 100) : 0;
                    $progress = min(100, max(0, $progress)); // Ensure between 0 and 100
                    
                    return [
                        'id' => $period->id,
                        'name' => $period->name,
                        'start_date' => $period->start_date,
                        'end_date' => $period->end_date,
                        'total_applicants' => $totalApplications,
                        'approval_rate' => $approvalRate,
                        'days_remaining' => $daysRemaining,
                        'progress' => $progress
                    ];
                });
                
            return response()->json([
                'data' => $currentPeriods
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching period stats', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get all statistics in one consolidated endpoint
     * This combines data from multiple endpoints to reduce API calls
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConsolidatedStats()
    {
        try {
            // Get all quick stats
            $dashboardStats = $this->getQuickStatsInternal();
            
            // Get application stats
            $applicationStats = $this->getApplicationStatsInternal();
            
            // Get period stats
            $periodStats = $this->getPeriodStatsInternal();
            
            return response()->json([
                'success' => true,
                'message' => 'Consolidated statistics retrieved successfully',
                'data' => [
                    'dashboard' => $dashboardStats,
                    'applications' => $applicationStats,
                    'periods' => $periodStats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error fetching consolidated statistics', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Internal method to get dashboard quick stats
     * 
     * @return array
     */
    private function getQuickStatsInternal()
    {
        // Get total applicants count - using BeasiswaApplication instead of CalonBeswan
        $total_pendaftar = BeasiswaApplication::distinct('beswan_id')->count('beswan_id');
        
        // Get total active beswan count
        $total_beswan = BeasiswaRecipients::count();
        
        // Get total mentors count
        $total_mentor = Mentor::count();
        
        // Get total document uploads count
        $total_dokumen = BeswanDocument::count();
        
        // Get verified documents count
        $dokumen_terverifikasi = BeswanDocument::where('status', 'verified')->count();
        
        return [
            'total_pendaftar' => $total_pendaftar,
            'total_beswan' => $total_beswan,
            'total_mentor' => $total_mentor,
            'total_dokumen' => $total_dokumen,
            'dokumen_terverifikasi' => $dokumen_terverifikasi
        ];
    }
    
    /**
     * Internal method to get application stats
     * 
     * @return array
     */
    private function getApplicationStatsInternal()
    {
        // Use the BeasiswaApplication model's status constants
        $total = BeasiswaApplication::count();
        $pending = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_PENDING)->count();
        $lolos_berkas = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_LOLOS_BERKAS)->count();
        $lolos_wawancara = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_LOLOS_WAWANCARA)->count();
        $diterima = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_DITERIMA)->count();
        $ditolak = BeasiswaApplication::where('status', BeasiswaApplication::STATUS_DITOLAK)->count();
        
        // Get applications by period
        $by_period = BeasiswaApplication::select('beasiswa_period_id', DB::raw('COUNT(*) as count'))
            ->groupBy('beasiswa_period_id')
            ->with('beasiswaPeriod:id,nama_periode,tahun')
            ->get()
            ->map(function ($item) {
                return [
                    'period_id' => $item->beasiswa_period_id,
                    'period_name' => $item->beasiswaPeriod ? 
                        $item->beasiswaPeriod->nama_periode . ' ' . $item->beasiswaPeriod->tahun : 
                        'Unknown Period',
                    'count' => $item->count
                ];
            });
            
        return [
            'total' => $total,
            'pending' => $pending,
            'lolos_berkas' => $lolos_berkas,
            'lolos_wawancara' => $lolos_wawancara,
            'diterima' => $diterima,
            'ditolak' => $ditolak,
            'by_period' => $by_period
        ];
    }
    
    /**
     * Internal method to get period stats
     * 
     * @return array
     */
    private function getPeriodStatsInternal()
    {
        $currentPeriods = BeasiswaPeriods::where('end_date', '>=', Carbon::now())
            ->withCount(['applications as total_applicants'])
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get()
            ->map(function ($period) {
                // Calculate days remaining
                $daysRemaining = Carbon::now()->diffInDays(Carbon::parse($period->end_date), false);
                $daysRemaining = max(0, $daysRemaining);
                
                return [
                    'id' => $period->id,
                    'name' => $period->name,
                    'start_date' => $period->start_date,
                    'end_date' => $period->end_date,
                    'total_applicants' => $period->total_applicants,
                    'days_remaining' => $daysRemaining
                ];
            });
            
        return $currentPeriods;
    }
}