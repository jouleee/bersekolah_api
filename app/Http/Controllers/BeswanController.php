<?php

namespace App\Http\Controllers;

use App\Models\Beswan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class BeswanController extends Controller
{
    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $beswan = Beswan::create($request->only([
                'nama_lengkap', 'nama_panggilan', 'tempat_lahir',
                'tanggal_lahir', 'jenis_kelamin', 'agama'
            ]));

       

            // Tidak insert dokumen_pendukung karena bisa lebih dari 1 dan diisi belakangan
        });

        return redirect()->back()->with('success', 'Pendaftaran berhasil dibuat!');
    }

    /**
     * Display a listing of beswans
     */
    public function index(Request $request)
    {
        try {
            // Simple query joining beswan with users
            $beswans = Beswan::join('users', 'beswan.user_id', '=', 'users.id')
                ->select(
                    'beswan.id',
                    'users.name as nama',
                    'users.email',
                    'users.phone',
                    'beswan.created_at'
                )
                ->orderBy('beswan.created_at', 'desc')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $beswans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified beswan
     */
    public function show($id)
    {
        try {
            $beswan = Beswan::with(['user', 'keluarga', 'sekolah', 'alamat'])
                ->findOrFail($id);
            
            // Tambahkan data user ke response
            $beswan->user_name = $beswan->user->name ?? null;
            $beswan->user_email = $beswan->user->email ?? null;
            $beswan->user_phone = $beswan->user->phone ?? null;
            
            return response()->json([
                'status' => 'success',
                'data' => $beswan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Beswan tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Return summary counts of beswan data
     */
    public function count(Request $request)
    {
        try {
            // Total pendaftar (semua beswan yang mendaftar)
            $totalPendaftar = Beswan::whereHas('beasiswaApplications')->count();

            // Total dokumen
            $totalDokumen = Beswan::withCount('documents')->get()->sum('documents_count');

            // Total beswan yang lolos (status accepted)
            $totalDiterima = Beswan::whereHas('beasiswaApplications', function ($q) {
                $q->where('status', 'diterima');
            })->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_beswan' => $totalPendaftar,
                    'total_documents' => $totalDokumen,
                    'total_diterima' => $totalDiterima,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified beswan
     */
    public function update(Request $request, $id)
    {
        try {
            $beswan = Beswan::findOrFail($id);
            
            $beswan->update($request->only([
                'nama_lengkap', 'nama_panggilan', 'tempat_lahir',
                'tanggal_lahir', 'jenis_kelamin', 'agama'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Data beswan berhasil diperbarui',
                'data' => $beswan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified beswan
     */
    public function destroy($id)
    {
        try {
            $beswan = Beswan::findOrFail($id);
            $beswan->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data beswan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accepted beswan with search functionality
     */
    public function getAcceptedBeswan(Request $request)
    {
        try {
            $query = Beswan::join('users', 'beswan.user_id', '=', 'users.id')
                ->join('beasiswa_applications', 'beswan.id', '=', 'beasiswa_applications.beswan_id')
                ->where('beasiswa_applications.status', 'diterima')
                ->select(
                    'beswan.id',
                    'beswan.nama_panggilan',
                    'beswan.tempat_lahir',
                    'beswan.tanggal_lahir',
                    'beswan.jenis_kelamin',
                    'beswan.agama',
                    'users.name as user_name',
                    'users.email',
                    'users.phone',
                    'beswan.created_at',
                    'beasiswa_applications.status as application_status',
                    'beasiswa_applications.submitted_at'
                );

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('beswan.nama_panggilan', 'LIKE', "%{$search}%")
                      ->orWhere('users.name', 'LIKE', "%{$search}%")
                      ->orWhere('users.email', 'LIKE', "%{$search}%")
                      ->orWhere('users.phone', 'LIKE', "%{$search}%");
                });
            }

            // Filter by period if provided
            if ($request->has('period_id') && $request->period_id) {
                $query->where('beasiswa_applications.beasiswa_period_id', $request->period_id);
            }

            $beswans = $query->orderBy('beswan.created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $beswans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update beswan application status to rejected
     */
    public function rejectBeswan($id)
    {
        try {
            $beswan = Beswan::findOrFail($id);
            
            // Update the latest beasiswa application status to rejected
            $application = $beswan->beasiswaApplications()
                ->latest()
                ->first();
                
            if (!$application) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada aplikasi beasiswa ditemukan untuk beswan ini'
                ], 404);
            }

            $application->update(['status' => 'ditolak']);

            return response()->json([
                'status' => 'success',
                'message' => 'Status beswan berhasil diubah menjadi tidak lolos'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

