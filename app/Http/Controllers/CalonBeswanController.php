<?php

namespace App\Http\Controllers;

use App\Models\CalonBeswan;
use App\Models\Beswan;
use App\Models\SekolahBeswan;
use App\Models\KeluargaBeswan;
use App\Models\AlamatBeswan;
use App\Models\BeswanDocument;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CalonBeswanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $calonBeswans = CalonBeswan::all();
        return response()->json($calonBeswans);
    }

    /**
     * Get data pribadi for authenticated user
     */
    public function getPribadi(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Load relasi beswan dari user
            $user->load('beswan');
            
            // Ambil data sekolah beswan jika ada
            $sekolahBeswan = null;
            if ($user->beswan) {
                $sekolahBeswan = SekolahBeswan::where('beswan_id', $user->beswan->id)->first();
            }
            
            // Debug: Log what we found
            Log::info('Data pribadi fetch result:', [
                'user_id' => $user->id,
                'beswan_exists' => $user->beswan ? true : false,
                'sekolah_exists' => $sekolahBeswan ? true : false,
                'sekolah_data' => $sekolahBeswan ? $sekolahBeswan->toArray() : null
            ]);
            
            // Prepare response data
            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'nama_lengkap' => $user->name, // Map name -> nama_lengkap untuk frontend
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'beswan' => $user->beswan ? [
                    'id' => $user->beswan->id,
                    'nama_panggilan' => $user->beswan->nama_panggilan,
                    'tempat_lahir' => $user->beswan->tempat_lahir,
                    'tanggal_lahir' => $user->beswan->tanggal_lahir,
                    'jenis_kelamin' => $user->beswan->jenis_kelamin,
                    'agama' => $user->beswan->agama,
                ] : null,
                'sekolah_beswan' => $sekolahBeswan ? [
                    'beswan_id' => $sekolahBeswan->beswan_id,
                    'asal_sekolah' => $sekolahBeswan->asal_sekolah,
                    'daerah_sekolah' => $sekolahBeswan->daerah_sekolah,
                    'jurusan' => $sekolahBeswan->jurusan,
                    'tingkat_kelas' => $sekolahBeswan->tingkat_kelas,
                ] : null
            ];

            return response()->json([
                'message' => 'Data pribadi berhasil diambil',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting data pribadi:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data',
                'errors' => ['server' => ['Gagal mengambil data pribadi']]
            ], 500);
        }
    }

    public function postPribadi(Request $request)
    {
        try {
            $user = auth()->user();

            // Debug: Log request data
            Log::info('Request data pribadi:', $request->all());

            $validatedData = $request->validate([
                // Data User
                'nama_lengkap' => 'required|string|max:255',
                'phone' => 'required|string|min:10|max:15|regex:/^[0-9]+$/',
                
                // Data Beswan (dari tabel beswan)
                'nama_panggilan' => 'required|string|max:255',
                'tempat_lahir' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date',
                'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
                'agama' => 'required|string|max:255',
                
                // Data Sekolah (dari tabel sekolah_beswans)
                'asal_sekolah' => 'nullable|string|max:255',
                'daerah_sekolah' => 'nullable|string|max:255',
                'jurusan' => 'nullable|string|max:255',
                'tingkat_kelas' => 'nullable|string|max:255',
            ]);

            Log::info('Validated data pribadi:', $validatedData);

            // Gunakan database transaction
            DB::beginTransaction();

            try {
                // Update user data
                $user->update([
                    'name' => $validatedData['nama_lengkap'],
                    'phone' => $validatedData['phone'],
                ]);

                Log::info('User updated successfully');

                // Update atau create data beswan
                $beswan = Beswan::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_panggilan' => $validatedData['nama_panggilan'],
                        'tempat_lahir' => $validatedData['tempat_lahir'],
                        'tanggal_lahir' => $validatedData['tanggal_lahir'],
                        'jenis_kelamin' => $validatedData['jenis_kelamin'],
                        'agama' => $validatedData['agama'],
                    ]
                );

                Log::info('Beswan updated/created with ID: ' . $beswan->id);

                // PERBAIKI: Update atau create data sekolah beswan
                $sekolahData = [
                    'asal_sekolah' => $validatedData['asal_sekolah'] ?? null,
                    'daerah_sekolah' => $validatedData['daerah_sekolah'] ?? null,
                    'jurusan' => $validatedData['jurusan'] ?? null,
                    'tingkat_kelas' => $validatedData['tingkat_kelas'] ?? null,
                ];

                Log::info('Preparing sekolah data:', $sekolahData);

                // FIXED: Gunakan $beswan->id (BUKAN $user->beswan->id)
                $sekolahBeswan = SekolahBeswan::updateOrCreate(
                    ['beswan_id' => $beswan->id], // ✅ BENAR: gunakan $beswan->id 
                    $sekolahData
                );

                Log::info('Sekolah beswan updated/created for beswan_id: ' . $beswan->id);

                DB::commit();
                Log::info('Transaction committed successfully');

                return response()->json([
                    'message' => 'Data pribadi berhasil disimpan.',
                    'data' => [
                        'user' => $user->fresh(),
                        'beswan' => $beswan->fresh(),
                        'sekolah_beswan' => $sekolahBeswan->fresh()
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Error in transaction data pribadi:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]);
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed data pribadi:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Data yang diberikan tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving data pribadi:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'errors' => ['server' => ['Gagal menyimpan data pribadi: ' . $e->getMessage()]]
            ], 500);
        }
    }

    /**
     * Get data keluarga for authenticated user
     */
    public function getKeluarga(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Load relasi beswan dari user
            $user->load('beswan');
            
            // Ambil data keluarga beswan jika ada
            $keluargaBeswan = null;
            if ($user->beswan) {
                $keluargaBeswan = KeluargaBeswan::where('beswan_id', $user->beswan->id)->first();
            }
            
            // Prepare response data
            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'nama_lengkap' => $user->name,
                    'email' => $user->email,
                ],
                'beswan' => $user->beswan ? [
                    'id' => $user->beswan->id,
                ] : null,
                'keluarga_beswan' => $keluargaBeswan ? [
                    'beswan_id' => $keluargaBeswan->beswan_id,
                    'nama_ayah' => $keluargaBeswan->nama_ayah,
                    'pekerjaan_ayah' => $keluargaBeswan->pekerjaan_ayah,
                    'penghasilan_ayah' => $keluargaBeswan->penghasilan_ayah,
                    'nama_ibu' => $keluargaBeswan->nama_ibu,
                    'pekerjaan_ibu' => $keluargaBeswan->pekerjaan_ibu,
                    'penghasilan_ibu' => $keluargaBeswan->penghasilan_ibu,
                    'jumlah_saudara' => $keluargaBeswan->jumlah_saudara_kandung,
                    'tanggungan_keluarga' => $keluargaBeswan->jumlah_tanggungan,
                ] : null
            ];

            return response()->json([
                'message' => 'Data keluarga berhasil diambil',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting data keluarga:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data',
                'errors' => ['server' => ['Gagal mengambil data keluarga']]
            ], 500);
        }
    }

    /**
     * Post data keluarga for authenticated user
     */
    public function postKeluarga(Request $request)
    {
        try {
            $user = auth()->user();

            // Debug: Log request data
            Log::info('Request data keluarga:', $request->all());

            $validatedData = $request->validate([
                'nama_ayah' => 'required|string|max:255',
                'pekerjaan_ayah' => 'required|string|max:255',
                'penghasilan_ayah' => 'required|string|max:255',
                'nama_ibu' => 'required|string|max:255',
                'pekerjaan_ibu' => 'required|string|max:255',
                'penghasilan_ibu' => 'required|string|max:255',
                'jumlah_saudara' => 'required|string|max:255',  // Field dari frontend
                'tanggungan_keluarga' => 'required|string|max:255',  // Field dari frontend
            ]);

            // Debug: Log validated data
            Log::info('Validated data keluarga:', $validatedData);

            // Gunakan database transaction untuk memastikan konsistensi data
            DB::beginTransaction();

            try {
                // Pastikan user memiliki data beswan terlebih dahulu
                $beswan = Beswan::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_panggilan' => $user->name, // default value
                        'tempat_lahir' => '',
                        'tanggal_lahir' => now(),
                        'jenis_kelamin' => 'Laki-laki',
                        'agama' => 'Islam',
                    ]
                );

                // Update atau create data keluarga beswan
                // Mapping field frontend ke database field yang benar
                $keluargaBeswan = KeluargaBeswan::updateOrCreate(
                    ['beswan_id' => $beswan->id],
                    [
                        'nama_ayah' => $validatedData['nama_ayah'],
                        'pekerjaan_ayah' => $validatedData['pekerjaan_ayah'],
                        'penghasilan_ayah' => $validatedData['penghasilan_ayah'],
                        'nama_ibu' => $validatedData['nama_ibu'],
                        'pekerjaan_ibu' => $validatedData['pekerjaan_ibu'],
                        'penghasilan_ibu' => $validatedData['penghasilan_ibu'],
                        'jumlah_saudara_kandung' => $validatedData['jumlah_saudara'],  // Frontend -> Database
                        'jumlah_tanggungan' => $validatedData['tanggungan_keluarga'],  // Frontend -> Database
                    ]
                );

                DB::commit();

                return response()->json([
                    'message' => 'Data keluarga berhasil disimpan.',
                    'data' => [
                        'user' => $user,
                        'beswan' => $beswan->fresh(),
                        'keluarga_beswan' => $keluargaBeswan->fresh()
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Error in transaction data keluarga:', ['error' => $e->getMessage()]);
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed data keluarga:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Data yang diberikan tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving data keluarga:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'errors' => ['server' => ['Gagal menyimpan data keluarga']]
            ], 500);
        }
    }

    /**
     * Get data alamat for authenticated user
     */
    public function getAlamat(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Load relasi beswan dari user
            $user->load('beswan');
            
            // Ambil data alamat beswan jika ada
            $alamatBeswan = null;
            if ($user->beswan) {
                $alamatBeswan = AlamatBeswan::where('beswan_id', $user->beswan->id)->first();
            }
            
            // Prepare response data
            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'nama_lengkap' => $user->name,
                    'email' => $user->email,
                ],
                'beswan' => $user->beswan ? [
                    'id' => $user->beswan->id,
                ] : null,
                'alamat_beswan' => $alamatBeswan ? [
                    'beswan_id' => $alamatBeswan->beswan_id,
                    'alamat' => $alamatBeswan->alamat_lengkap,
                    'rt' => $alamatBeswan->rt,
                    'rw' => $alamatBeswan->rw,
                    'kelurahan' => $alamatBeswan->kelurahan_desa,
                    'kecamatan' => $alamatBeswan->kecamatan,
                    'kota' => $alamatBeswan->kota_kabupaten,
                    'provinsi' => $alamatBeswan->provinsi,
                    'kode_pos' => $alamatBeswan->kode_pos,
                    'nomor_telepon' => $alamatBeswan->nomor_telepon,
                    'email' => $alamatBeswan->email,
                    'telepon_darurat' => $alamatBeswan->kontak_darurat,
                ] : null
            ];

            return response()->json([
                'message' => 'Data alamat berhasil diambil',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error getting data alamat:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data',
                'errors' => ['server' => ['Gagal mengambil data alamat']]
            ], 500);
        }
    }

    /**
     * Post data alamat for authenticated user
     */
    public function postAlamat(Request $request)
    {
        try {
            $user = auth()->user();

            // Debug: Log request data
            Log::info('Request data alamat:', $request->all());
            Log::info('User data:', ['user_id' => $user->id, 'email' => $user->email]);

            $validatedData = $request->validate([
                'alamat' => 'required|string|min:10',
                'rt' => 'required|string|max:10',
                'rw' => 'required|string|max:10',
                'kelurahan' => 'required|string|max:255',
                'kecamatan' => 'required|string|max:255',
                'kota' => 'required|string|max:255',
                'provinsi' => 'required|string|max:255',
                'kode_pos' => 'required|string|min:5|max:10',
                'nomor_telepon' => 'required|string|min:10|max:15',
                'email' => 'required|email|max:255',
                'telepon_darurat' => 'required|string|min:10|max:15',
            ]);

            // Debug: Log validated data
            Log::info('Validated data alamat:', $validatedData);

            // Gunakan database transaction untuk memastikan konsistensi data
            DB::beginTransaction();

            try {
                // Pastikan user memiliki data beswan terlebih dahulu
                $beswan = Beswan::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_panggilan' => $user->name, // default value
                        'tempat_lahir' => 'Unknown',
                        'tanggal_lahir' => now(),
                        'jenis_kelamin' => 'Laki-laki',
                        'agama' => 'Islam',
                    ]
                );

                Log::info('Beswan data:', ['beswan_id' => $beswan->id, 'user_id' => $beswan->user_id]);

                // Prepare data untuk alamat beswan
                $alamatData = [
                    'alamat_lengkap' => $validatedData['alamat'],
                    'rt' => $validatedData['rt'],
                    'rw' => $validatedData['rw'],
                    'kelurahan_desa' => $validatedData['kelurahan'],
                    'kecamatan' => $validatedData['kecamatan'],
                    'kota_kabupaten' => $validatedData['kota'],
                    'provinsi' => $validatedData['provinsi'],
                    'kode_pos' => $validatedData['kode_pos'],
                    'nomor_telepon' => $validatedData['nomor_telepon'],
                    'email' => $validatedData['email'],
                    'kontak_darurat' => $validatedData['telepon_darurat'],
                ];

                Log::info('Alamat data to save:', $alamatData);

                // Cek apakah sudah ada data alamat
                $existingAlamat = AlamatBeswan::where('beswan_id', $beswan->id)->first();
                
                if ($existingAlamat) {
                    // Update existing record
                    Log::info('Updating existing alamat record');
                    $existingAlamat->update($alamatData);
                    $alamatBeswan = $existingAlamat->fresh();
                } else {
                    // Create new record
                    Log::info('Creating new alamat record');
                    $alamatData['beswan_id'] = $beswan->id;
                    $alamatBeswan = AlamatBeswan::create($alamatData);
                }

                Log::info('Alamat beswan saved:', ['alamat_id' => $alamatBeswan->beswan_id]);

                DB::commit();

                return response()->json([
                    'message' => 'Data alamat berhasil disimpan.',
                    'data' => [
                        'user' => $user,
                        'beswan' => $beswan->fresh(),
                        'alamat_beswan' => $alamatBeswan->fresh()
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Error in transaction data alamat:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]);
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed data alamat:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Data yang diberikan tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving data alamat:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'errors' => ['server' => ['Gagal menyimpan data alamat: ' . $e->getMessage()]]
            ], 500);
        }
    }

    /**
     * Get documents by category for admin
     */
    public function getDocumentsByCategory(Request $request, $category = null)
    {
        try {
            Log::info("Getting documents by category: " . ($category ?? 'all'));
            Log::info("Request parameters: " . json_encode($request->all()));

            // Query dasar untuk BeswanDocument dengan relasi user
            $query = BeswanDocument::with(['user:id,name,email']);
            
            // Filter berdasarkan kategori jika ada
            if ($category && $category !== 'semua' && $category !== 'all') {
                // Untuk kategori 'wajib', kita filter berdasarkan document_type yang termasuk kategori wajib
                if ($category === 'wajib') {
                    $wajibTypes = ['student_proof', 'identity_proof', 'photo'];
                    $query->whereIn('document_type', $wajibTypes);
                } elseif ($category === 'sosial_media') {
                    $sosmedTypes = ['instagram_follow', 'twibbon_post'];
                    $query->whereIn('document_type', $sosmedTypes);
                } elseif ($category === 'pendukung') {
                    $pendukungTypes = ['achievement_certificate', 'recommendation_letter', 'essay_motivation', 'cv_resume', 'other_document'];
                    $query->whereIn('document_type', $pendukungTypes);
                }
            }
            
            // Filter berdasarkan status jika ada
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Filter berdasarkan user_id jika ada
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            
            // Ambil data dengan sorting
            $documents = $query->orderBy('created_at', 'desc')->get();
            
            Log::info("Found " . $documents->count() . " documents");
            
            // Transform data untuk frontend
            $transformedDocuments = $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'user_id' => $doc->user_id,
                    'document_type' => $doc->document_type,
                    'file_path' => Storage::disk('public')->url($doc->file_path),
                    'file_name' => $doc->file_name,
                    'status' => $doc->status,
                    'keterangan' => $doc->keterangan,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                    'user' => $doc->user,
                    'document_type_info' => [
                        'name' => $this->getDocumentTypeName($doc->document_type),
                        'description' => $this->getDocumentTypeDescription($doc->document_type)
                    ]
                ];
            });
            
            return response()->json([
                'message' => 'Documents retrieved successfully',
                'data' => $transformedDocuments,
                'total' => $transformedDocuments->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in getDocumentsByCategory: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Failed to retrieve documents',
                'error' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Helper method untuk mendapatkan nama document type
     */
    private function getDocumentTypeName($code)
    {
        $typeMap = [
            'student_proof' => 'Bukti Status Siswa',
            'identity_proof' => 'Identitas Diri', 
            'photo' => 'Foto Diri',
            'instagram_follow' => 'Bukti Follow Instagram',
            'twibbon_post' => 'Postingan Twibbon',
            'achievement_certificate' => 'Sertifikat Prestasi',
            'recommendation_letter' => 'Surat Rekomendasi',
            'essay_motivation' => 'Essay Motivasi',
            'cv_resume' => 'CV/Resume',
            'other_document' => 'Dokumen Lainnya'
        ];
        
        return $typeMap[$code] ?? $code;
    }

    /**
     * Helper method untuk mendapatkan deskripsi document type
     */
    private function getDocumentTypeDescription($code)
    {
        $descMap = [
            'student_proof' => 'Bukti status sebagai siswa aktif',
            'identity_proof' => 'Kartu identitas diri (KTP/KK)',
            'photo' => 'Foto diri terbaru',
            'instagram_follow' => 'Screenshot bukti follow Instagram',
            'twibbon_post' => 'Screenshot postingan twibbon',
            'achievement_certificate' => 'Sertifikat prestasi akademik/non-akademik',
            'recommendation_letter' => 'Surat rekomendasi dari institusi',
            'essay_motivation' => 'Essay motivasi mengikuti beasiswa',
            'cv_resume' => 'Curriculum Vitae atau Resume',
            'other_document' => 'Dokumen pendukung lainnya'
        ];
        
        return $descMap[$code] ?? '';
    }
}
