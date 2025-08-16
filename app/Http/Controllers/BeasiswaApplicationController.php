<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeasiswaApplicationRequest;
use App\Http\Requests\UpdateBeasiswaApplicationRequest;
use App\Models\BeasiswaApplication;
use App\Models\Beswan;
use App\Models\BeasiswaPeriods;
use App\Models\BeswanDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BeasiswaApplicationController extends Controller
{
    /**
     * Finalize aplikasi beasiswa
     */
    public function finalizeApplication(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            Log::info('Finalize application request from user: ' . $user->id);

            // 1. Cek apakah user memiliki data beswan
            $beswan = Beswan::where('user_id', $user->id)->first();
            if (!$beswan) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Data beswan tidak ditemukan. Silakan lengkapi data pribadi terlebih dahulu.',
                    ],
                    400,
                );
            }

            // 2. Cek apakah sudah ada aplikasi yang finalized untuk periode aktif
            $existingApplication = BeasiswaApplication::where('beswan_id', $beswan->id)->whereNotNull('finalized_at')->first();

            if ($existingApplication) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Aplikasi beasiswa sudah pernah dikirim sebelumnya.',
                        'data' => [
                            'application_id' => $existingApplication->id,
                            'finalized_at' => $existingApplication->finalized_at,
                            'status' => $existingApplication->status,
                        ],
                    ],
                    400,
                );
            }

            // 3. Ambil periode beasiswa yang sedang aktif
            $activePeriod = BeasiswaPeriods::where('status', 'active')->where('is_active', true)->where('mulai_pendaftaran', '<=', Carbon::now())->where('akhir_pendaftaran', '>=', Carbon::now())->first();

            if (!$activePeriod) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Tidak ada periode beasiswa yang sedang aktif saat ini.',
                    ],
                    400,
                );
            }

            Log::info('Found active period: ' . $activePeriod->id);

            // 4. ✅ FIXED: Validasi kelengkapan dokumen wajib dengan cara yang benar
            $requiredDocumentCodes = ['student_proof', 'identity_proof', 'photo', 'instagram_follow', 'twibbon_post'];

            $missingDocuments = [];
            $unverifiedDocuments = [];

            foreach ($requiredDocumentCodes as $docCode) {
                // Cek apakah dokumen ada dan terverifikasi
                $verifiedDoc = $beswan
                    ->documents()
                    ->where('status', 'verified')
                    ->whereHas('documentType', function ($q) use ($docCode) {
                        $q->where('code', $docCode);
                    })
                    ->first();

                if (!$verifiedDoc) {
                    // Cek apakah dokumen ada tapi belum terverifikasi
                    $existingDoc = $beswan
                        ->documents()
                        ->whereHas('documentType', function ($q) use ($docCode) {
                            $q->where('code', $docCode);
                        })
                        ->first();

                    if ($existingDoc) {
                        if ($existingDoc->status === 'pending') {
                            $unverifiedDocuments[] = $docCode;
                        } elseif ($existingDoc->status === 'rejected') {
                            $missingDocuments[] = $docCode . ' (ditolak, perlu upload ulang)';
                        }
                    } else {
                        $missingDocuments[] = $docCode;
                    }
                }
            }

            // Jika ada dokumen yang belum terverifikasi
            if (!empty($unverifiedDocuments)) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Masih ada dokumen yang belum terverifikasi.',
                        'unverified_documents' => $unverifiedDocuments,
                    ],
                    400,
                );
            }

            // Jika ada dokumen yang hilang
            if (!empty($missingDocuments)) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Masih ada dokumen wajib yang belum diunggah atau perlu diperbaiki.',
                        'missing_documents' => $missingDocuments,
                    ],
                    400,
                );
            }

            // 5. Validasi kelengkapan data pribadi, keluarga, dan alamat
            $dataValidation = $this->validateUserData($beswan);
            if (!$dataValidation['valid']) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Data pribadi belum lengkap.',
                        'missing_data' => $dataValidation['missing'],
                    ],
                    400,
                );
            }

            // 6. ✅ FIXED: Buat atau update aplikasi beasiswa
            $application = BeasiswaApplication::where('beswan_id', $beswan->id)->where('beasiswa_period_id', $activePeriod->id)->first();

            if (!$application) {
                // Buat aplikasi baru
                $application = BeasiswaApplication::create([
                    'beswan_id' => $beswan->id,
                    'beasiswa_period_id' => $activePeriod->id,
                    'status' => BeasiswaApplication::STATUS_PENDING,
                    'submitted_at' => Carbon::now(),
                    'finalized_at' => Carbon::now(),
                ]);

                Log::info('Created new application: ' . $application->id);
            } else {
                // Update aplikasi yang sudah ada
                $application->update([
                    'finalized_at' => Carbon::now(),
                    'submitted_at' => $application->submitted_at ?? Carbon::now(),
                    'status' => BeasiswaApplication::STATUS_PENDING, // ✅ Pastikan status pending
                ]);

                Log::info('Updated existing application: ' . $application->id);
            }

            DB::commit();

            // 7. Load relasi untuk response
            $application->load(['beswan.user', 'beasiswaPeriod']);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Aplikasi beasiswa berhasil dikirim!',
                    'data' => [
                        'application_id' => $application->id,
                        'beswan_name' => $application->beswan->nama_panggilan ?? $application->beswan->user->name,
                        'user_name' => $application->beswan->user->name,
                        'period' => $application->beasiswaPeriod->tahun,
                        'period_name' => $application->beasiswaPeriod->nama_periode,
                        'status' => $application->status,
                        'finalized_at' => $application->finalized_at,
                        'submitted_at' => $application->submitted_at,
                    ],
                ],
                200,
            );
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error finalizing application: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat mengirim aplikasi beasiswa.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ],
                500,
            );
        }
    }

    /**
     * Cek status aplikasi user saat ini
     */
    public function getApplicationStatus(Request $request)
    {
        try {
            $user = $request->user();

            // Get active beswan record
            $beswan = Beswan::where('user_id', $user->id)->first();
            if (!$beswan) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Beswan record not found',
                    ],
                    404,
                );
            }

            // Get latest application
            $application = BeasiswaApplication::where('beswan_id', $beswan->id)
                ->with(['beasiswaPeriod'])
                ->latest()
                ->first();

            if (!$application) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No application found',
                    ],
                    404,
                );
            }

            // ✅ FIXED: Format date dan time dengan benar
            $responseData = [
                'application_id' => $application->id,
                'status' => $application->status,
                'finalized_at' => $application->finalized_at,
                'submitted_at' => $application->submitted_at,
                'is_finalized' => !is_null($application->finalized_at),
                'can_edit' => $application->canEdit(),
                // ✅ FIXED: Return date dan time dalam format yang benar
                'interview_date' => $application->interview_date ? $application->interview_date->format('Y-m-d') : null,
                'interview_time' => $application->interview_time ? $application->interview_time->format('H:i:s') : null,
                'interview_link' => $application->interview_link,
                'catatan_admin' => $application->catatan_admin,
                'period' => [
                    'id' => $application->beasiswaPeriod->id,
                    'tahun' => $application->beasiswaPeriod->tahun,
                    'nama_periode' => $application->beasiswaPeriod->nama_periode,
                    'mulai_pendaftaran' => $application->beasiswaPeriod->mulai_pendaftaran,
                    'akhir_pendaftaran' => $application->beasiswaPeriod->akhir_pendaftaran,
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Application status retrieved successfully',
                'data' => $responseData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting application status: ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to get application status',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ],
                500,
            );
        }
    }

    /**
     * Cek apakah user bisa finalize
     */
    public function checkFinalizationEligibility(Request $request)
    {
        try {
            $user = Auth::user();

            $beswan = Beswan::where('user_id', $user->id)->first();
            if (!$beswan) {
                return response()->json(
                    [
                        'success' => true,
                        'can_finalize' => false,
                        'message' => 'Data beswan tidak ditemukan.',
                    ],
                    200,
                );
            }

            // Cek periode aktif
            $activePeriod = BeasiswaPeriods::where('status', 'active')->where('is_active', true)->where('mulai_pendaftaran', '<=', Carbon::now())->where('akhir_pendaftaran', '>=', Carbon::now())->first();

            if (!$activePeriod) {
                return response()->json(
                    [
                        'success' => true,
                        'can_finalize' => false,
                        'message' => 'Tidak ada periode beasiswa yang aktif saat ini.',
                    ],
                    200,
                );
            }

            // Cek apakah sudah finalized
            $existingApplication = BeasiswaApplication::where('beswan_id', $beswan->id)->whereNotNull('finalized_at')->first();

            if ($existingApplication) {
                return response()->json(
                    [
                        'success' => true,
                        'can_finalize' => false,
                        'message' => 'Aplikasi sudah pernah dikirim.',
                        'application' => $existingApplication,
                    ],
                    200,
                );
            }

            // Cek kelengkapan dokumen dan data
            $documentCheck = $this->checkDocumentCompleteness($beswan);
            $dataCheck = $this->validateUserData($beswan);

            $canFinalize = $documentCheck['complete'] && $dataCheck['valid'];

            return response()->json(
                [
                    'success' => true,
                    'can_finalize' => $canFinalize,
                    'message' => $canFinalize ? 'Semua persyaratan telah terpenuhi.' : 'Masih ada dokumen atau data yang belum lengkap.',
                    'document_status' => $documentCheck,
                    'data_status' => $dataCheck,
                    'active_period' => [
                        'id' => $activePeriod->id,
                        'tahun' => $activePeriod->tahun,
                        'nama_periode' => $activePeriod->nama_periode,
                        'akhir_pendaftaran' => $activePeriod->akhir_pendaftaran,
                    ],
                ],
                200,
            );
        } catch (\Exception $e) {
            Log::error('Error checking finalization eligibility: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'can_finalize' => false,
                    'message' => 'Terjadi kesalahan saat mengecek kelayakan finalisasi.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ],
                500,
            );
        }
    }

    /**
     * Validate kelengkapan data user
     */
    private function validateUserData($beswan)
    {
        $missing = [];
        $valid = true;

        // Cek data user
        $user = $beswan->user;
        if (!$user->name || !$user->email || !$user->phone) {
            $missing[] = 'Data user (nama, email, telepon)';
            $valid = false;
        }

        // Cek data beswan
        if (!$beswan->nama_panggilan || !$beswan->tempat_lahir || !$beswan->tanggal_lahir || !$beswan->jenis_kelamin || !$beswan->agama) {
            $missing[] = 'Data pribadi beswan';
            $valid = false;
        }

        // Cek data keluarga
        $keluarga = $beswan->keluarga;
        if (!$keluarga || !$keluarga->nama_ayah || !$keluarga->nama_ibu) {
            $missing[] = 'Data keluarga';
            $valid = false;
        }

        // Cek data alamat
        $alamat = $beswan->alamat;
        if (!$alamat || !$alamat->alamat_lengkap || !$alamat->provinsi) {
            $missing[] = 'Data alamat';
            $valid = false;
        }

        return [
            'valid' => $valid,
            'missing' => $missing,
        ];
    }

    /**
     * Check kelengkapan dokumen wajib
     */
    private function checkDocumentCompleteness($beswan)
    {
        $requiredDocumentTypes = ['student_proof', 'identity_proof', 'photo', 'instagram_follow', 'twibbon_post'];

        $verifiedCount = 0;
        $missingDocs = [];

        foreach ($requiredDocumentTypes as $docType) {
            $doc = BeswanDocument::where('beswan_id', $beswan->id)->where('document_type', $docType)->where('status', 'verified')->first();

            if ($doc) {
                $verifiedCount++;
            } else {
                $missingDocs[] = $docType;
            }
        }

        return [
            'complete' => $verifiedCount === count($requiredDocumentTypes),
            'verified_count' => $verifiedCount,
            'total_required' => count($requiredDocumentTypes),
            'missing_documents' => $missingDocs,
        ];
    }
}
