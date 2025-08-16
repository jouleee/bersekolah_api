<?php

namespace App\Http\Controllers;

use App\Models\Beswan;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use App\Models\BeswanDocument;
use Illuminate\Support\Facades\DB;
use App\Models\BeasiswaApplication;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BesWanDocumentController extends Controller
{
    /**
     * Check if user can edit documents (not finalized)
     */
    private function checkCanEdit()
    {
        $user = Auth::user();
        $beswan = Beswan::where('user_id', $user->id)->first();
        
        if (!$beswan) {
            return ['can_edit' => false, 'message' => 'Data beswan tidak ditemukan.'];
        }

        // Check if application is finalized
        $finalizedApplication = BeasiswaApplication::where('beswan_id', $beswan->id)
            ->whereNotNull('finalized_at')
            ->first();

        if ($finalizedApplication) {
            return [
                'can_edit' => false, 
                'message' => 'Aplikasi beasiswa sudah dikirim dan tidak dapat diubah lagi.',
                'finalized_at' => $finalizedApplication->finalized_at,
                'application_status' => $finalizedApplication->status
            ];
        }

        return ['can_edit' => true, 'beswan' => $beswan];
    }

    /**
     * Upload document dengan check finalized
     */
    public function store(Request $request)
    {
        // Check if can edit
        $editCheck = $this->checkCanEdit();
        if (!$editCheck['can_edit']) {
            return response()->json([
                'message' => $editCheck['message'],
                'can_edit' => false,
                'finalized_at' => $editCheck['finalized_at'] ?? null,
                'application_status' => $editCheck['application_status'] ?? null
            ], 403);
        }

        $beswan = $editCheck['beswan'];

        // Existing validation and upload logic...
        $validator = Validator::make($request->all(), [
            'document_type_id' => 'required|exists:document_types,id',
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $documentType = DocumentType::findOrFail($request->document_type_id);
            
            // Check if document already exists and verified
            $existingDoc = BeswanDocument::where('beswan_id', $beswan->id)
                ->where('document_type_id', $request->document_type_id)
                ->where('status', 'verified')
                ->first();

            if ($existingDoc) {
                return response()->json([
                    'message' => 'Dokumen jenis ini sudah terverifikasi dan tidak dapat diubah.',
                    'existing_document' => $existingDoc
                ], 400);
            }

            // Map document types to folder paths
            $folderMap = [
                'student_proof' => 'beswan/student_proof',
                'identity_proof' => 'beswan/identity_proof', 
                'photo' => 'beswan/photo',
                'instagram_follow' => 'beswan/instagram_follow',
                'twibbon_post' => 'beswan/twibbon_post',
                'achievement_certificate' => 'beswan/achievement_certificate',
                'essay_motivation' => 'beswan/essay_motivation'
            ];

            $folderPath = $folderMap[$documentType->code] ?? 'beswan/documents';

            // Upload file
            $file = $request->file('file');
            $filename = time() . '_' . $beswan->id . '_' . $documentType->code . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folderPath, $filename, 'public');

            // Delete existing document if any
            if ($existingDoc) {
                if (Storage::disk('public')->exists($existingDoc->file_path)) {
                    Storage::disk('public')->delete($existingDoc->file_path);
                }
                $existingDoc->delete();
            }

            // Create new document
            $document = BeswanDocument::create([
                'beswan_id' => $beswan->id,
                'document_type_id' => $request->document_type_id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'pending'
            ]);

            $document->load('documentType');

            Log::info("Document uploaded: {$document->id} by user {$beswan->user_id}");

            return response()->json([
                'message' => 'Dokumen berhasil diunggah',
                'data' => $document
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengunggah dokumen',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update document dengan check finalized
     */
    public function update(Request $request, $id)
    {
        // Check if can edit
        $editCheck = $this->checkCanEdit();
        if (!$editCheck['can_edit']) {
            return response()->json([
                'message' => $editCheck['message'],
                'can_edit' => false,
                'finalized_at' => $editCheck['finalized_at'] ?? null,
                'application_status' => $editCheck['application_status'] ?? null
            ], 403);
        }

        $beswan = $editCheck['beswan'];

        // Existing update logic...
        $document = BeswanDocument::where('beswan_id', $beswan->id)->findOrFail($id);

        // Check if document is verified
        if ($document->status === 'verified') {
            return response()->json([
                'message' => 'Dokumen yang sudah terverifikasi tidak dapat diubah.',
                'document_status' => $document->status
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Delete old file
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Map document types to folder paths
            $folderMap = [
                'student_proof' => 'beswan/student_proof',
                'identity_proof' => 'beswan/identity_proof', 
                'photo' => 'beswan/photo',
                'instagram_follow' => 'beswan/instagram_follow',
                'twibbon_post' => 'beswan/twibbon_post',
                'achievement_certificate' => 'beswan/achievement_certificate',
                'essay_motivation' => 'beswan/essay_motivation'
            ];

            $folderPath = $folderMap[$document->documentType->code] ?? 'beswan/documents';

            // Upload new file
            $file = $request->file('file');
            $filename = time() . '_' . $beswan->id . '_' . $document->documentType->code . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folderPath, $filename, 'public');

            // Update document
            $document->update([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'pending' // Reset to pending when updated
            ]);

            $document->load('documentType');

            Log::info("Document updated: {$document->id} by user {$beswan->user_id}");

            return response()->json([
                'message' => 'Dokumen berhasil diperbarui',
                'data' => $document
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating document: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui dokumen',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete document dengan check finalized
     */
    public function destroy($id)
    {
        // Check if can edit
        $editCheck = $this->checkCanEdit();
        if (!$editCheck['can_edit']) {
            return response()->json([
                'message' => $editCheck['message'],
                'can_edit' => false,
                'finalized_at' => $editCheck['finalized_at'] ?? null,
                'application_status' => $editCheck['application_status'] ?? null
            ], 403);
        }

        $beswan = $editCheck['beswan'];

        try {
            $document = BeswanDocument::where('beswan_id', $beswan->id)->findOrFail($id);

            // Check if document is verified
            if ($document->status === 'verified') {
                return response()->json([
                    'message' => 'Dokumen yang sudah terverifikasi tidak dapat dihapus.',
                    'document_status' => $document->status
                ], 400);
            }

            // Delete file
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            Log::info("Document deleted: {$id} by user {$beswan->user_id}");

            return response()->json([
                'message' => 'Dokumen berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus dokumen',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get edit status for frontend
     */
    public function getEditStatus()
    {
        $editCheck = $this->checkCanEdit();
        
        return response()->json([
            'can_edit' => $editCheck['can_edit'],
            'message' => $editCheck['message'] ?? 'Dokumen dapat diedit',
            'finalized_at' => $editCheck['finalized_at'] ?? null,
            'application_status' => $editCheck['application_status'] ?? null
        ], 200);
    }

    /**
     * Ambil dokumen berdasarkan user login - FIXED: Tanpa filter category
     */
    public function getDokumenWajib(Request $request)
    {
        try {
            $user = Auth::user();
            
            Log::info("Getting ALL documents for user ID: {$user->id}");
            
            // Get beswan data for user
            $beswan = Beswan::where('user_id', $user->id)->first();
            
            // Jika belum ada data beswan, return empty
            if (!$beswan) {
                Log::info("No beswan data found for user: {$user->id}");
                return response()->json([
                    'message' => 'Data beswan belum ada, silakan lengkapi data pribadi terlebih dahulu',
                    'data' => []
                ]);
            }

            // ✅ FIXED: Get ALL user's uploaded documents (tanpa filter category)
            $uploadedDocs = BeswanDocument::where('beswan_id', $beswan->id)
                ->with(['documentType', 'verifiedBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info("Found " . $uploadedDocs->count() . " uploaded documents for beswan ID: {$beswan->id}");

            // ✅ FIXED: Format response data dengan informasi lengkap
            $documents = $uploadedDocs->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'document_type' => [
                        'id' => $doc->documentType->id,
                        'name' => $doc->documentType->name,
                        'code' => $doc->documentType->code,
                        'category' => $doc->documentType->category ?? 'wajib',
                    ],
                    'document_type_code' => $doc->documentType->code, // ✅ TAMBAHKAN untuk compatibility
                    'file_name' => $doc->file_name,
                    'file_path' => $doc->file_path,
                    'file_type' => $doc->file_type,
                    'file_size' => $doc->file_size,
                    'status' => $doc->status,
                    'keterangan' => $doc->keterangan,
                    'verified_at' => $doc->verified_at,
                    'verified_by' => $doc->verifiedBy ? [
                        'id' => $doc->verifiedBy->id,
                        'name' => $doc->verifiedBy->name,
                    ] : null,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                ];
            });

            return response()->json([
                'message' => 'Data dokumen berhasil diambil',
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getDokumenWajib: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengambil data dokumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all document types berdasarkan kategori - FIXED
     */
    public function getDocumentTypes(Request $request)
    {
        try {
            $category = $request->get('category', 'wajib');
            
            Log::info("Getting document types for category: {$category}");
            
            $documentTypes = DocumentType::where('category', $category)
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->map(function($docType) {
                    return [
                        'id' => $docType->id,
                        'code' => $docType->code,
                        'name' => $docType->name,
                        'description' => $docType->description,
                        'category' => $docType->category,
                        'is_required' => $docType->is_required,
                        // PARSE JSON STRING TO ARRAY
                        'allowed_formats' => is_string($docType->allowed_formats) 
                            ? json_decode($docType->allowed_formats, true) 
                            : ($docType->allowed_formats ?? []),
                        'max_file_size' => $docType->max_file_size,
                        'is_active' => $docType->is_active,
                        'created_at' => $docType->created_at->toISOString(),
                        'updated_at' => $docType->updated_at->toISOString(),
                    ];
                });

            Log::info("Found " . $documentTypes->count() . " document types for category: {$category}");

            return response()->json([
                'message' => 'Document types berhasil diambil',
                'data' => $documentTypes
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getDocumentTypes: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengambil document types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload document - FIX VALIDATION
     */
    public function uploadDocument(Request $request, $documentCode)
    {
        try {
            Log::info("=== UPLOAD DOCUMENT START ===");
            Log::info("Document code: {$documentCode}");
            
            $user = Auth::user();
            Log::info("User ID: {$user->id}");
            
            // Get beswan data for user
            $beswan = Beswan::where('user_id', $user->id)->first();
            
            // Pastikan user memiliki data beswan terlebih dahulu
            if (!$beswan) {
                Log::info("No beswan data, creating default beswan for user: {$user->id}");
                
                // Create default beswan record jika belum ada
                $beswan = Beswan::create([
                    'user_id' => $user->id,
                    'nama_panggilan' => $user->name,
                    'tempat_lahir' => 'Unknown',
                    'tanggal_lahir' => now(),
                    'jenis_kelamin' => 'Laki-laki',
                    'agama' => 'Islam',
                ]);
            }

            $beswanId = $beswan->id;
            Log::info("Beswan ID: {$beswanId}");
            
            // Check if document type exists
            Log::info("Looking for document type with code: {$documentCode}");
            $documentType = DocumentType::where('code', $documentCode)
                ->where('is_active', true)
                ->first();

            if (!$documentType) {
                Log::error("Document type not found: {$documentCode}");
                
                // Debug: List all available document types
                $allTypes = DocumentType::all(['code', 'name', 'is_active']);
                Log::info("Available document types: " . json_encode($allTypes));
                
                // Try to find similar document types (for debugging purposes)
                $similar = DocumentType::where('code', 'like', "%{$documentCode}%")
                    ->orWhere('name', 'like', "%{$documentCode}%")
                    ->get(['code', 'name', 'is_active']);
                Log::info("Similar document types: " . json_encode($similar));
                
                return response()->json([
                    'message' => 'Tipe dokumen tidak ditemukan: ' . $documentCode,
                    'code' => $documentCode,
                    'available_types' => $allTypes,
                    'debug_info' => config('app.debug') ? [
                        'submitted_code' => $documentCode,
                        'similar_types' => $similar ?? []
                    ] : null
                ], 404);
            }

            Log::info("Document type found: " . json_encode($documentType->toArray()));

            // Parse allowed formats - FIXED
            $allowedFormats = [];
            if (is_string($documentType->allowed_formats)) {
                $allowedFormats = json_decode($documentType->allowed_formats, true) ?? [];
            } else if (is_array($documentType->allowed_formats)) {
                $allowedFormats = $documentType->allowed_formats;
            }
            
            if (empty($allowedFormats)) {
                $allowedFormats = ['jpg', 'jpeg', 'png', 'pdf']; // Default
            }
            
            $maxSize = $documentType->max_file_size ?? 10485760; // 10MB default

            Log::info("Validation rules - Max size: " . ($maxSize / 1024) . "KB, Formats: " . implode(',', $allowedFormats));

            // Validate request - IMPROVED
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'max:' . ($maxSize / 1024), // Laravel expects KB
                    'mimes:' . implode(',', $allowedFormats)
                ],
                'keterangan' => 'nullable|string|max:500'
            ]);

            Log::info("Validation passed");

            $file = $request->file('file');
            Log::info("File info: " . json_encode([
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'type' => $file->getClientMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]));

            // Upload file - IMPROVED DIRECTORY STRUCTURE
            $category = $documentType->category ?? 'unknown';
            $directory = "dokumen-{$category}/{$documentCode}";
            $extension = $file->getClientOriginalExtension();
            $fileName = $beswanId . '_' . $documentCode . '_' . time() . '.' . $extension;

            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
                Log::info("Directory created: {$directory}");
            }

            $path = Storage::disk('public')->putFileAs($directory, $file, $fileName);

            if (!$path) {
                throw new \Exception('Gagal menyimpan file');
            }

            Log::info("File uploaded successfully: {$path}");

            // Database transaction for document save
            DB::beginTransaction();
            
            try {
                // For all document types, check if already exists
                $existingDoc = BeswanDocument::where('beswan_id', $beswanId)
                    ->where('document_type_id', $documentType->id)
                    ->first();
                    
                Log::info("Existing document check: " . ($existingDoc ? "Found ID {$existingDoc->id}" : "Not found"));

                if ($existingDoc) {
                    Log::info("Updating existing document ID: {$existingDoc->id}");
                    
                    // Delete old file
                    if ($existingDoc->file_path && Storage::disk('public')->exists($existingDoc->file_path)) {
                        Storage::disk('public')->delete($existingDoc->file_path);
                        Log::info("Old file deleted: {$existingDoc->file_path}");
                    }
                    
                    // Update existing record
                    $updateData = [
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'status' => 'pending',
                        'keterangan' => $request->keterangan,
                        'verified_at' => null,
                        'verified_by' => null,
                    ];
                    
                    Log::info("Update data: " . json_encode($updateData));
                    
                    $existingDoc->update($updateData);
                    $document = $existingDoc->fresh();
                    
                    Log::info("Document updated successfully. New data: " . json_encode($document->toArray()));
                    
                } else {
                    Log::info("Creating new document record");
                    
                    $createData = [
                        'beswan_id' => $beswanId,
                        'document_type_id' => $documentType->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'status' => 'pending',
                        'keterangan' => $request->keterangan,
                    ];
                    
                    Log::info("Create data: " . json_encode($createData));
                    
                    $document = BeswanDocument::create($createData);
                    
                    Log::info("Document created successfully. ID: {$document->id}");
                }
                
                DB::commit();
                Log::info("Database transaction committed");
                
            } catch (\Exception $dbError) {
                DB::rollback();
                Log::error("Database error: " . $dbError->getMessage());
                Log::error("Database stack trace: " . $dbError->getTraceAsString());
                
                // Delete uploaded file if database save failed
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    Log::info("Uploaded file deleted due to database error");
                }
                
                throw $dbError;
            }

            $responseData = [
                'id' => $document->id,
                'document_type' => $documentType->code,
                'file_path' => asset(Storage::url($path)),
                'file_name' => $file->getClientOriginalName(),
                'status' => 'pending',
                'created_at' => $document->updated_at->toISOString(),
            ];

            Log::info("Response data: " . json_encode($responseData));
            Log::info("=== UPLOAD DOCUMENT SUCCESS ===");

            return response()->json([
                'message' => $documentType->name . ' berhasil diunggah',
                'data' => $responseData
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in uploadDocument: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Gagal mengunggah dokumen',
                'error' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Update status dokumen (untuk admin)
     */
    public function updateStatus(Request $request, $documentId)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,verified,rejected',
                'keterangan' => 'nullable|string|max:500'
            ]);

            $document = BeswanDocument::findOrFail($documentId);
            
            $document->update([
                'status' => $request->status,
                'keterangan' => $request->keterangan,
                'verified_at' => $request->status !== 'pending' ? now() : null,
                'verified_by' => $request->status !== 'pending' ? Auth::id() : null,
            ]);

            return response()->json([
                'message' => 'Status dokumen berhasil diperbarui',
                'data' => $document->load('documentType')
            ]);

        } catch (\Exception $e) {
            Log::error('Error in updateStatus: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memperbarui status dokumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete document
     */
    public function deleteDocument($documentId)
    {
        try {
            $user = Auth::user();
            $beswan = Beswan::where('user_id', $user->id)->first();

            if (!$beswan) {
                return response()->json([
                    'message' => 'Data beswan tidak ditemukan'
                ], 404);
            }

            // Find document dan pastikan milik user ini
            $document = BeswanDocument::where('id', $documentId)
                ->where('beswan_id', $beswan->id)
                ->first();

            if (!$document) {
                return response()->json([
                    'message' => 'Dokumen tidak ditemukan'
                ], 404);
            }

            // Delete file dari storage
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
                Log::info("File deleted: {$document->file_path}");
            }

            // Delete record dari database
            $document->delete();
            Log::info("Document deleted: ID {$documentId}");

            return response()->json([
                'message' => 'Dokumen berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menghapus dokumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // /**
    //  * Get documents by category (untuk admin)
    //  */
    // public function getDocumentsByCategory(Request $request, $category = 'wajib')
    // {
    //     try {
    //         $perPage = $request->get('per_page', 15);
    //         $status = $request->get('status');
            
    //         $query = BeswanDocument::with(['documentType', 'beswan'])
    //             ->whereHas('documentType', function($q) use ($category) {
    //                 $q->where('category', $category);
    //             });

    //         if ($status) {
    //             $query->where('status', $status);
    //         }

    //         $documents = $query->orderBy('created_at', 'desc')
    //             ->paginate($perPage);

    //         return response()->json([
    //             'message' => 'Data dokumen berhasil diambil',
    //             'data' => $documents
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Error in getDocumentsByCategory: ' . $e->getMessage());
    //         return response()->json([
    //             'message' => 'Gagal mengambil data dokumen',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Get documents by category for admin
     */
    public function getDocumentsByCategory(Request $request, $category = null)
    {
        try {
            Log::info("Getting documents by category: " . ($category ?? 'all'));
            Log::info("Request parameters: " . json_encode($request->all()));

            // PERBAIKAN: Load relasi yang benar
            $query = BeswanDocument::with([
                'beswan:id,user_id,nama_panggilan', // Load beswan data
                'beswan.user:id,name,email',        // Load user melalui beswan
                'documentType'
            ]);
            
            // Filter berdasarkan kategori jika ada
            if ($category && $category !== 'semua' && $category !== 'all') {
                $query->whereHas('documentType', function($q) use ($category) {
                    $q->where('category', $category);
                });
            }
            
            // Filter berdasarkan status jika ada
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Filter berdasarkan user_id jika ada
            if ($request->has('user_id')) {
                // PERBAIKAN: Filter berdasarkan user_id melalui relasi beswan
                $query->whereHas('beswan', function($q) use ($request) {
                    $q->where('user_id', $request->user_id);
                });
            }
            
            // Ambil data dengan sorting
            $documents = $query->orderBy('created_at', 'desc')->get();
            
            Log::info("Found " . $documents->count() . " documents");
            
            // Transform data untuk frontend
            $transformedDocuments = $documents->map(function ($doc) {
                // PERBAIKAN: Ambil user data dari relasi yang benar
                $userData = null;
                if ($doc->beswan && $doc->beswan->user) {
                    $userData = [
                        'id' => $doc->beswan->user->id,
                        'name' => $doc->beswan->user->name,
                        'email' => $doc->beswan->user->email
                    ];
                } else {
                    // Fallback jika struktur data tidak konsisten
                    $userData = [
                        'id' => null,
                        'name' => 'User Not Found',
                        'email' => 'unknown@email.com'
                    ];
                }

                return [
                    'id' => $doc->id,
                    'user_id' => $userData['id'], // User ID yang sebenarnya
                    'beswan_id' => $doc->beswan_id, // Untuk debugging
                    'document_type' => $doc->documentType ? $doc->documentType->code : 'unknown',
                    'file_path' => asset(Storage::url($doc->file_path)),
                    'file_name' => $doc->file_name,
                    'status' => $doc->status,
                    'keterangan' => $doc->keterangan,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                    'user' => $userData, // Data user yang benar
                    'document_type_info' => [
                        'name' => $doc->documentType ? $doc->documentType->name : 'Unknown',
                        'description' => $doc->documentType ? $doc->documentType->description : ''
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

    /**
     * Get document statistics for admin dashboard
     */
    public function getDocumentStatistics()
    {
        try {
            $stats = [
                'total' => BeswanDocument::count(),
                'pending' => BeswanDocument::where('status', 'pending')->count(),
                'verified' => BeswanDocument::where('status', 'verified')->count(),
                'rejected' => BeswanDocument::where('status', 'rejected')->count(),
                'by_category' => []
            ];
            
            // Stats by category
            $categories = ['wajib', 'sosial_media', 'pendukung'];
            foreach ($categories as $category) {
                $stats['by_category'][$category] = [
                    'total' => BeswanDocument::whereHas('documentType', function($q) use ($category) {
                        $q->where('category', $category);
                    })->count(),
                    'pending' => BeswanDocument::whereHas('documentType', function($q) use ($category) {
                        $q->where('category', $category);
                    })->where('status', 'pending')->count()
                ];
            }
            
            return response()->json([
                'message' => 'Statistics retrieved successfully', 
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in getDocumentStatistics: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}