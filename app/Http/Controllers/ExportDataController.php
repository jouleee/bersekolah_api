<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Beswan;
use App\Models\CalonBeswan;
use App\Models\BeasiswaPeriods;
use App\Models\BeasiswaApplication;
use App\Models\BeasiswaRecipients;
use App\Models\BeswanDocument;
use App\Models\DocumentType;
use App\Models\Announcement;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class ExportDataController extends Controller
{    /**
     * Handle the export data request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        // Get tables as comma-separated string and convert to array
        $tablesString = $request->query('tables');
        $tables = $tablesString ? explode(',', $tablesString) : [];
        
        // Validate request
        $validator = Validator::make([
            'tables' => $tables,
            'format' => $request->query('format'),
            'dateRange' => $request->query('dateRange', 'all'),
        ], [
            'tables' => 'required|array',
            'format' => 'required|string|in:csv,excel,json,zip',
            'dateRange' => 'nullable|string|in:all,today,this_week,this_month,this_year',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        // Check permission (admin/superadmin only)
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized: Anda tidak memiliki akses untuk mengekspor data'], 403);
        }

        try {
            // Get selected tables
            $tables = $tables;
            $format = $request->query('format');
            $dateRange = $request->query('dateRange', 'all');
            
            // Debug: Log request details
            Log::info('Export request received', [
                'tables' => $tables,
                'format' => $format,
                'dateRange' => $dateRange
            ]);
            
            // Validasi kombinasi table dan format
            $validation = $this->validateTableFormatCombination($tables, $format);
            if ($validation !== true) {
                Log::warning('Format validation failed', ['validation' => $validation]);
                return response()->json(['message' => $validation], 400);
            }

            // Collect data from selected tables
            Log::info('About to collect data', ['tables' => $tables, 'format' => $format]);
            $exportData = $this->collectData($tables, $dateRange, $format);
            Log::info('Data collected successfully', ['data_count' => count($exportData)]);
            
            // Generate export file based on format
            switch ($format) {
                case 'excel':
                    return $this->exportToExcel($exportData, $tables);
                case 'csv':
                    return $this->exportToCsv($exportData, $tables);
                case 'json':
                    return $this->exportToJson($exportData);
                case 'zip':
                    Log::info('About to export to ZIP');
                    return $this->exportToZip($exportData, $tables);
                default:
                    return response()->json(['message' => 'Format tidak didukung'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Export data error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'tables' => $tables ?? 'unknown',
                'format' => $format ?? 'unknown'
            ]);
            return response()->json([
                'message' => 'Gagal mengekspor data: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }    /**
     * Collect data from selected tables based on date range
     *
     * @param array $tables
     * @param string $dateRange
     * @param string $format
     * @return array
     */
    private function collectData(array $tables, string $dateRange, string $format = 'excel')
    {
        $exportData = [];
        $dateFilter = $this->getDateRangeFilter($dateRange);

        try {
            foreach ($tables as $table) {
                try {
                    switch ($table) {
                        case 'users':
                            $query = User::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            $exportData[$table] = $query->get();
                            break;

                        case 'data_beswan':
                            // Ambil data dari beasiswa_applications yang sudah terdaftar
                            // lalu join dengan tabel terkait
                            $query = BeasiswaApplication::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            
                            // Join dengan semua tabel terkait
                            $applications = $query->with([
                                'beswan.user',                    // Data user
                                'beswan.alamatBeswan',           // Data alamat
                                'beswan.keluargaBeswan',         // Data keluarga
                                'beswan.sekolahBeswan',          // Data sekolah
                                'beswan',                        // Data beswan utama
                                'beasiswaPeriod'                 // Data periode beasiswa
                            ])->get();
                            
                            // Flatten data untuk export dengan urutan yang rapi
                            $enrichedData = $applications->map(function($app) {
                                $data = [];
                                
                                // 1. Data Pribadi
                                $data['beswan_id'] = $app->beswan->id ?? null;
                                $data['nama_panggilan'] = $app->beswan->nama_panggilan ?? null;
                                $data['nama_lengkap'] = $app->beswan->user->name ?? null;
                                $data['tempat_lahir'] = $app->beswan->tempat_lahir ?? null;
                                $data['tanggal_lahir'] = $app->beswan->tanggal_lahir ?? null;
                                $data['jenis_kelamin'] = $app->beswan->jenis_kelamin ?? null;
                                $data['agama'] = $app->beswan->agama ?? null;
                                $data['email'] = $app->beswan->user->email ?? null;
                                $data['phone'] = $app->beswan->user->phone ?? null;
                                
                                // 2. Alamat
                                if ($app->beswan && $app->beswan->alamatBeswan) {
                                    $alamat = $app->beswan->alamatBeswan;
                                    $data['alamat_lengkap'] = $alamat->alamat_lengkap;
                                    $data['rt'] = $alamat->rt;
                                    $data['rw'] = $alamat->rw;
                                    $data['kelurahan_desa'] = $alamat->kelurahan_desa;
                                    $data['kecamatan'] = $alamat->kecamatan;
                                    $data['kota_kabupaten'] = $alamat->kota_kabupaten;
                                    $data['provinsi'] = $alamat->provinsi;
                                    $data['kode_pos'] = $alamat->kode_pos;
                                    $data['nomor_telepon'] = $alamat->nomor_telepon;
                                    $data['kontak_darurat'] = $alamat->kontak_darurat;
                                } else {
                                    $data['alamat_lengkap'] = null;
                                    $data['rt'] = null;
                                    $data['rw'] = null;
                                    $data['kelurahan_desa'] = null;
                                    $data['kecamatan'] = null;
                                    $data['kota_kabupaten'] = null;
                                    $data['provinsi'] = null;
                                    $data['kode_pos'] = null;
                                    $data['nomor_telepon'] = null;
                                    $data['kontak_darurat'] = null;
                                }
                                
                                // 3. Data Keluarga
                                if ($app->beswan && $app->beswan->keluargaBeswan) {
                                    $keluarga = $app->beswan->keluargaBeswan;
                                    $data['nama_ayah'] = $keluarga->nama_ayah;
                                    $data['pekerjaan_ayah'] = $keluarga->pekerjaan_ayah;
                                    $data['penghasilan_ayah'] = $keluarga->penghasilan_ayah;
                                    $data['nama_ibu'] = $keluarga->nama_ibu;
                                    $data['pekerjaan_ibu'] = $keluarga->pekerjaan_ibu;
                                    $data['penghasilan_ibu'] = $keluarga->penghasilan_ibu;
                                    $data['jumlah_saudara_kandung'] = $keluarga->jumlah_saudara_kandung;
                                    $data['jumlah_tanggungan'] = $keluarga->jumlah_tanggungan;
                                } else {
                                    $data['nama_ayah'] = null;
                                    $data['pekerjaan_ayah'] = null;
                                    $data['penghasilan_ayah'] = null;
                                    $data['nama_ibu'] = null;
                                    $data['pekerjaan_ibu'] = null;
                                    $data['penghasilan_ibu'] = null;
                                    $data['jumlah_saudara_kandung'] = null;
                                    $data['jumlah_tanggungan'] = null;
                                }
                                
                                // 4. Data Pendidikan
                                if ($app->beswan && $app->beswan->sekolahBeswan) {
                                    $sekolah = $app->beswan->sekolahBeswan;
                                    $data['asal_sekolah'] = $sekolah->asal_sekolah;
                                    $data['daerah_sekolah'] = $sekolah->daerah_sekolah;
                                    $data['jurusan'] = $sekolah->jurusan;
                                    $data['tingkat_kelas'] = $sekolah->tingkat_kelas;
                                } else {
                                    $data['asal_sekolah'] = null;
                                    $data['daerah_sekolah'] = null;
                                    $data['jurusan'] = null;
                                    $data['tingkat_kelas'] = null;
                                }
                                
                                // 5. Data Aplikasi Beasiswa
                                $data['application_id'] = $app->id;
                                $data['status_diterima'] = $app->status;
                                
                                return $data;
                            });
                            
                            $exportData[$table] = $enrichedData;
                            break;

                        case 'dokumen_beswan':
                            // Ambil data dari beasiswa_applications dengan beswan dan documents
                            Log::info('Starting dokumen_beswan collection');
                            
                            $applications = BeasiswaApplication::with([
                                'beswan.user'
                            ])->get();
                            
                            // Debug: Log jumlah aplikasi
                            Log::info('Export request for dokumen_beswan', [
                                'applications_count' => $applications->count(),
                                'format' => $format
                            ]);
                            
                            if ($format === 'zip') {
                                Log::info('Processing ZIP format for dokumen_beswan');
                                
                                // Untuk ZIP: Organize documents by user untuk ZIP export
                                $organizedDocs = [];
                                foreach ($applications as $app) {
                                    Log::info('Processing application', [
                                        'app_id' => $app->id,
                                        'has_beswan' => $app->beswan ? true : false,
                                        'has_user' => $app->beswan && $app->beswan->user ? true : false
                                    ]);
                                    
                                    if ($app->beswan && $app->beswan->user) {
                                        $userName = $app->beswan->user->name ?? 'Unknown';
                                        $cleanUserName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $userName);
                                        
                                        Log::info('Processing user', [
                                            'user_name' => $userName,
                                            'clean_name' => $cleanUserName,
                                            'beswan_id' => $app->beswan->id
                                        ]);
                                        
                                        // Ambil dokumen dari beswan menggunakan BeswanDocument model
                                        $documents = BeswanDocument::where('beswan_id', $app->beswan->id)
                                            ->with('documentType')
                                            ->get();
                                        
                                        Log::info('Found documents', [
                                            'beswan_id' => $app->beswan->id,
                                            'document_count' => $documents->count()
                                        ]);
                                        
                                        if ($documents->count() > 0) {
                                            if (!isset($organizedDocs[$cleanUserName])) {
                                                $organizedDocs[$cleanUserName] = [];
                                            }
                                            
                                            foreach ($documents as $doc) {
                                                Log::info('Processing document', [
                                                    'doc_id' => $doc->id,
                                                    'file_name' => $doc->file_name,
                                                    'document_type_id' => $doc->document_type_id,
                                                    'document_type' => $doc->documentType ? $doc->documentType->name : 'unknown',
                                                    'file_path' => $doc->file_path
                                                ]);
                                                
                                                // Buat nama file yang lebih deskriptif
                                                $originalFileName = $doc->file_name ?? 'unknown';
                                                $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
                                                $documentType = $doc->documentType ? strtolower($doc->documentType->name) : 'dokumen';
                                                $newFileName = $cleanUserName . '_' . $documentType . '.' . $fileExtension;
                                                
                                                // Konstruksi path berdasarkan struktur folder yang sebenarnya
                                                $filePath = null;
                                                if ($doc->file_path) {
                                                    $filePath = storage_path('app/public/' . $doc->file_path);
                                                }
                                                
                                                // Jika file_path tidak ada, coba cari di berbagai folder
                                                if (!$filePath || !file_exists($filePath)) {
                                                    $possiblePaths = [
                                                        'dokumen-wajib/identity_proof/' . $originalFileName,
                                                        'dokumen-wajib/photo/' . $originalFileName,
                                                        'dokumen-wajib/student_proof/' . $originalFileName,
                                                        'dokumen-sosial_media/instagram_follow/' . $originalFileName,
                                                        'dokumen-sosial_media/twibbon_post/' . $originalFileName,
                                                        'dokumen-pendukung/achievement_certificate/' . $originalFileName,
                                                        'dokumen-pendukung/essay_motivation/' . $originalFileName,
                                                    ];
                                                    
                                                    foreach ($possiblePaths as $path) {
                                                        $fullPath = storage_path('app/public/' . $path);
                                                        if (file_exists($fullPath)) {
                                                            $filePath = $fullPath;
                                                            Log::info('Found file in alternate path', [
                                                                'original_path' => $doc->file_path,
                                                                'found_path' => $path,
                                                                'full_path' => $fullPath
                                                            ]);
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                $docData = [
                                                    'file_name' => $originalFileName,
                                                    'new_file_name' => $newFileName,
                                                    'file_path' => $filePath,
                                                    'document_type' => $doc->documentType ? $doc->documentType->name : null,
                                                    'status' => $doc->status,
                                                    'created_at' => $doc->created_at,
                                                    'keterangan' => $doc->keterangan,
                                                ];
                                                
                                                Log::info('Document data prepared', [
                                                    'doc_data' => $docData,
                                                    'file_exists' => file_exists($filePath ?? '')
                                                ]);
                                                
                                                $organizedDocs[$cleanUserName][] = $docData;
                                            }
                                        }
                                    }
                                }
                                
                                Log::info('Organized documents completed', [
                                    'users_count' => count($organizedDocs),
                                    'user_names' => array_keys($organizedDocs)
                                ]);
                                
                                $exportData[$table] = $organizedDocs;
                            } else {
                                // Untuk Excel/CSV/JSON: Flatten data untuk export dokumen
                                $enrichedData = $applications->map(function($app) {
                                    $data = [];
                                    
                                    // 1. Data Pribadi
                                    $data['beswan_id'] = $app->beswan->id ?? null;
                                    $data['nama_panggilan'] = $app->beswan->nama_panggilan ?? null;
                                    $data['nama_lengkap'] = $app->beswan->user->name ?? null;
                                    $data['email'] = $app->beswan->user->email ?? null;
                                    
                                    // 2. Data Aplikasi Beasiswa
                                    $data['application_id'] = $app->id;
                                    $data['status_diterima'] = $app->status;
                                    
                                    // 3. Data Dokumen (jika ada)
                                    if ($app->beswan) {
                                        $documents = BeswanDocument::where('beswan_id', $app->beswan->id)
                                            ->with('documentType')
                                            ->get();
                                        
                                        if ($documents->count() > 0) {
                                            $data['jumlah_dokumen'] = $documents->count();
                                            
                                            // List dokumen yang diupload
                                            $documentList = [];
                                            foreach ($documents as $doc) {
                                                $documentList[] = [
                                                    'nama_file' => $doc->file_name ?? null,
                                                    'jenis_dokumen' => $doc->documentType ? $doc->documentType->name : null,
                                                    'ukuran_file' => $doc->file_size ?? null,
                                                    'tanggal_upload' => $doc->created_at ?? null,
                                                    'status' => $doc->status ?? null,
                                                    'keterangan' => $doc->keterangan ?? null
                                                ];
                                            }
                                            $data['dokumen_detail'] = json_encode($documentList);
                                        } else {
                                            $data['jumlah_dokumen'] = 0;
                                            $data['dokumen_detail'] = null;
                                        }
                                    } else {
                                        $data['jumlah_dokumen'] = 0;
                                        $data['dokumen_detail'] = null;
                                    }
                                    
                                    return $data;
                                });
                                $exportData[$table] = $enrichedData;
                            }
                            break;

                        case 'calon_beswans':
                            $query = CalonBeswan::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            $exportData[$table] = $query->with(['alamat', 'keluarga', 'dokumen', 'aplikasi'])->get();
                            break;

                        case 'beswans':
                            // Gabungkan data Beswan dengan data terkait
                            $query = Beswan::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            
                            // Gunakan relations yang ada untuk mendapatkan data terkait
                            $beswans = $query->with([
                                'user',
                                'alamatBeswan', 
                                'keluargaBeswan',
                                'documents'
                            ])->get();
                            
                            // Buat data yang lebih lengkap dan flat
                            $enrichedBeswans = $beswans->map(function($beswan) {
                                $data = is_object($beswan) && method_exists($beswan, 'toArray') ? $beswan->toArray() : (array)$beswan;
                                // Flatten alamat jika tersedia
                                if (isset($data['alamat_beswan']) && !empty($data['alamat_beswan'])) {
                                    $alamat = $data['alamat_beswan'];
                                    $data['alamat'] = isset($alamat['alamat']) ? $alamat['alamat'] : null;
                                    $data['kota'] = isset($alamat['kota']) ? $alamat['kota'] : null;
                                    $data['provinsi'] = isset($alamat['provinsi']) ? $alamat['provinsi'] : null;
                                    $data['kode_pos'] = isset($alamat['kode_pos']) ? $alamat['kode_pos'] : null;
                                }
                                // Tambahkan data user jika tersedia
                                if (isset($data['user']) && !empty($data['user'])) {
                                    $data['user_email'] = $data['user']['email'] ?? null;
                                    $data['user_status'] = $data['user']['status'] ?? null;
                                }
                                // Tambahkan data periode jika tersedia
                                if (isset($data['periode']) && !empty($data['periode'])) {
                                    $data['periode_tahun'] = $data['periode']['tahun'] ?? null;
                                    $data['periode_nama'] = $data['periode']['nama'] ?? null;
                                }
                                // Hapus objek bersarang untuk flatten data
                                unset($data['alamat_beswan']);
                                unset($data['user']);
                                unset($data['periode']);
                                return $data;
                            });
                            
                            $exportData[$table] = $enrichedBeswans;
                            break;

                        case 'beasiswa_applications':
                            $query = BeasiswaApplication::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            // Tambahkan informasi terkait
                            $exportData[$table] = $query->with(['user', 'beswan', 'beasiswaPeriod'])->get();
                            break;

                        case 'beasiswa_periods':
                            $query = BeasiswaPeriods::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            $exportData[$table] = $query->get();
                            break;

                        case 'documents':
                            $query = DocumentType::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            $exportData[$table] = $query->get();
                            break;
                            
                        case 'announcements':
                            $query = Announcement::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            $exportData[$table] = $query->get();
                            break;
                            
                        case 'faqs':
                            $query = Faq::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            $exportData[$table] = $query->get();
                            break;
                        
                        default:
                            // For any other table, we'll leave it empty to be safe
                            $exportData[$table] = collect([]);
                    }
                    
                    // Validasi data untuk pastikan tidak ada yang error
                    if (empty($exportData[$table]) || 
                        (is_array($exportData[$table]) && count($exportData[$table]) === 0) || 
                        (is_object($exportData[$table]) && method_exists($exportData[$table], 'count') && $exportData[$table]->count() === 0)) {
                        $exportData[$table] = collect([
                            ['info' => 'Tidak ada data tersedia untuk tabel ini']
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error dan berikan data kosong untuk tabel yang bermasalah
                    Log::error("Error collecting data for table {$table}: " . $e->getMessage());
                    $exportData[$table] = collect([
                        ['error' => "Gagal mengambil data: " . $e->getMessage()]
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('General error in collectData: ' . $e->getMessage());
            throw $e;
        }

        return $exportData;
    }

    /**
     * Get date range filter based on selected option
     *
     * @param string $dateRange
     * @return array
     */
    private function getDateRangeFilter(string $dateRange)
    {
        $now = Carbon::now();

        switch ($dateRange) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];
            case 'this_week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek(),
                ];
            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];
            case 'this_year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear(),
                ];
            default:
                return [
                    'start' => Carbon::createFromTimestamp(0),
                    'end' => $now,
                ];
        }
    }

    /**
     * Export data to Excel format
     *
     * @param array $data
     * @param array $tables
     * @return \Illuminate\Http\Response
     */    private function exportToExcel(array $data, array $tables)
    {
        // Create a new Spreadsheet
        $spreadsheet = new Spreadsheet();
        
        // Use the default first sheet instead of removing it
        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();
        $activeSheet->setTitle('Summary');
        $activeSheet->setCellValue('A1', 'Bersekolah Data Export');
        $activeSheet->setCellValue('A2', 'Exported on: ' . date('Y-m-d H:i:s'));
        $activeSheet->setCellValue('A3', 'Tables included: ' . implode(', ', $tables));
        
        // Create a sheet for each table
        foreach ($tables as $index => $tableName) {
            try {
                // Create a new sheet with safe name
                $sheet = $spreadsheet->createSheet();
                $safeName = preg_replace('/[\[\]:*?\/\\\\]/', '_', $tableName); // Replace invalid chars
                $sheet->setTitle(substr($safeName, 0, 31)); // Excel sheet names limited to 31 chars
                
                if (!empty($data[$tableName])) {
                    // Add headers
                    if (count($data[$tableName]) > 0) {
                        $firstRow = $data[$tableName][0];
                        $rowData = is_object($firstRow) && method_exists($firstRow, 'toArray') ? $firstRow->toArray() : (array)$firstRow;
                        $headers = array_keys($rowData);
                        $sheet->fromArray([$headers], NULL, 'A1');
                        
                        // Style headers
                        $headerStyle = [
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'color' => ['rgb' => 'EEEEEE']
                            ]
                        ];
                        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
                        
                        // Add data rows
                        $dataArray = [];
                        foreach ($data[$tableName] as $row) {
                            $rowArray = is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : (array)$row;
                            // Convert object or array values to JSON string to prevent Excel issues
                            foreach ($rowArray as $key => $value) {
                                if (is_array($value) || is_object($value)) {
                                    $rowArray[$key] = json_encode($value);
                                }
                            }
                            $dataArray[] = array_values($rowArray);
                        }
                        
                        if (!empty($dataArray)) {
                            $sheet->fromArray($dataArray, NULL, 'A2');
                        }
                        
                        // Auto size columns
                        foreach (range('A', $sheet->getHighestColumn()) as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }
                    } else {
                        $sheet->setCellValue('A1', 'No data available for this table');
                    }
                } else {
                    $sheet->setCellValue('A1', 'No data available for this table');
                }
            } catch (\Exception $e) {
                // If there's an error with one sheet, continue with others
                Log::error("Error creating sheet for $tableName: " . $e->getMessage());
                continue;
            }
        }

        // Set first sheet as active
        $spreadsheet->setActiveSheetIndex(0);
        
        // Create response
        $filename = 'bersekolah_export_excel_' . date('Y-m-d') . '.xlsx';
        
        // Configure the writer with better compatibility options
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        
        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($tempFile);
        
        // Get file size for content-length header
        $fileSize = filesize($tempFile);
        
        // Set proper headers for Excel files
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => $fileSize,
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
            'Expires' => '0'
        ])->deleteFileAfterSend(true);
    }
    
    /**
     * Export data to CSV format
     *
     * @param array $data
     * @param array $tables
     * @return \Illuminate\Http\Response
     */    private function exportToCsv(array $data, array $tables)
    {
        // Since CSV can only have one sheet, we'll combine all selected tables
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Export Data');
        
        $rowCount = 1;
        
        try {
            // Add export information header
            $sheet->setCellValue('A' . $rowCount, 'BERSEKOLAH DATA EXPORT');
            $sheet->getStyle('A' . $rowCount)->getFont()->setBold(true);
            $rowCount++;
            
            $sheet->setCellValue('A' . $rowCount, 'Exported on: ' . date('Y-m-d H:i:s'));
            $rowCount++;
            
            $sheet->setCellValue('A' . $rowCount, 'Tables included: ' . implode(', ', $tables));
            $rowCount++;
            
            $rowCount++; // Add empty row
            
            foreach ($tables as $tableName) {
                if (!empty($data[$tableName]) && $data[$tableName]->count() > 0) {
                    try {
                        // Add table name as header
                        $sheet->setCellValue('A' . $rowCount, strtoupper($tableName));
                        $sheet->getStyle('A' . $rowCount)->getFont()->setBold(true);
                        $rowCount++;
                        
                        // Convert to array properly
                        $firstRow = $data[$tableName][0];
                        $rowData = $firstRow instanceof \Illuminate\Database\Eloquent\Model ? $firstRow->toArray() : (array)$firstRow;
                        
                        // Add headers
                        $headers = array_keys($rowData);
                        $colIndex = 'A';
                        foreach ($headers as $header) {
                            $sheet->setCellValue($colIndex . $rowCount, $header);
                            $colIndex++;
                        }
                        $sheet->getStyle('A' . $rowCount . ':' . $sheet->getHighestColumn() . $rowCount)->getFont()->setBold(true);
                        $rowCount++;
                        
                        // Add data
                        foreach ($data[$tableName] as $row) {
                            $rowData = $row instanceof \Illuminate\Database\Eloquent\Model ? $row->toArray() : (array)$row;
                            $colIndex = 'A';
                            foreach ($rowData as $value) {
                                // Handle array or object values
                                if (is_array($value) || is_object($value)) {
                                    $value = json_encode($value);
                                } elseif ($value === null) {
                                    $value = ''; // Convert null to empty string
                                }
                                
                                $sheet->setCellValue($colIndex . $rowCount, $value);
                                $colIndex++;
                            }
                            $rowCount++;
                        }
                    } catch (\Exception $e) {
                        // If there's an error with one table, add error message and continue with others
                        $sheet->setCellValue('A' . $rowCount, "Error processing table: " . $e->getMessage());
                        $rowCount++;
                    }
                    
                    // Add space between tables
                    $rowCount++;
                } else {
                    $sheet->setCellValue('A' . $rowCount, strtoupper($tableName) . " - No data available");
                    $rowCount++;
                    $rowCount++; // Add empty row
                }
            }
        } catch (\Exception $e) {
            // If there's a general error, add an error message
            $sheet->setCellValue('A1', "Error processing export: " . $e->getMessage());
        }
        
        // Create response
        $filename = 'bersekolah_export_csv_' . date('Y-m-d') . '.csv';
        
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setLineEnding("\r\n");
        $writer->setSheetIndex(0); // Export first sheet
        
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($tempFile);
        
        // Get file size for content-length header
        $fileSize = filesize($tempFile);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => $fileSize,
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
            'Expires' => '0'
        ])->deleteFileAfterSend(true);
    }
    /**
     * Export data to JSON format
     *
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    private function exportToJson(array $data)
    {
        // Format the data with metadata
        $exportData = [
            'meta' => [
                'exported_at' => Carbon::now()->toIso8601String(),
                'exported_by' => Auth::user()->name,
                'tables_count' => count($data),
                'tables' => array_keys($data),
            ],
            'data' => $data,
        ];
        
        // Create response
        $filename = 'bersekolah_export_json_' . date('Y-m-d') . '.json';
        
        return response()->json($exportData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
            'Expires' => '0'
        ]);
    }

    /**
     * Export data to ZIP format (for documents)
     *
     * @param array $data
     * @param array $tables
     * @return \Illuminate\Http\Response
     */
    private function exportToZip(array $data, array $tables)
    {
        Log::info('Starting exportToZip', [
            'tables' => $tables,
            'data_keys' => array_keys($data),
            'data_counts' => array_map('count', $data)
        ]);
        
        // Create a temporary ZIP file
        $zipFileName = 'bersekolah_export_' . date('Y-m-d') . '.zip';
        $zipPath = tempnam(sys_get_temp_dir(), 'export') . '.zip';
        
        Log::info('Creating ZIP file', [
            'zip_path' => $zipPath,
            'zip_filename' => $zipFileName
        ]);
        
        $zip = new \ZipArchive();
        $result = $zip->open($zipPath, \ZipArchive::CREATE);
        if ($result !== TRUE) {
            Log::error('Cannot create ZIP file', ['error_code' => $result]);
            throw new \Exception("Cannot create ZIP file. Error code: $result");
        }
        
        Log::info('ZIP file created successfully');
        
        try {
            // Add Excel data file to ZIP
            if (in_array('data_beswan', $tables) && isset($data['data_beswan']) && !empty($data['data_beswan'])) {
                Log::info('Adding Excel data to ZIP', ['data_count' => count($data['data_beswan'])]);
                $excelContent = $this->createExcelContent($data['data_beswan'], 'data_beswan');
                $zip->addFromString('data_beswan.xlsx', $excelContent);
                Log::info('Excel data added successfully');
            }
            
            // Add documents if dokumen_beswan is selected
            if (in_array('dokumen_beswan', $tables) && isset($data['dokumen_beswan']) && !empty($data['dokumen_beswan'])) {
                Log::info('Adding documents to ZIP', [
                    'user_folders' => array_keys($data['dokumen_beswan']),
                    'folder_count' => count($data['dokumen_beswan'])
                ]);
                
                foreach ($data['dokumen_beswan'] as $userFolder => $documents) {
                    Log::info('Processing user folder', [
                        'user_folder' => $userFolder,
                        'document_count' => count($documents)
                    ]);
                    
                    // Create folder for each user
                    $zip->addEmptyDir("dokumen/{$userFolder}");
                    
                    foreach ($documents as $doc) {
                        Log::info('Processing document', [
                            'document' => $doc,
                            'file_exists' => file_exists($doc['file_path'] ?? '')
                        ]);
                        
                        if ($doc['file_path'] && file_exists($doc['file_path'])) {
                            // Use the new descriptive filename
                            $zip->addFile($doc['file_path'], "dokumen/{$userFolder}/" . $doc['new_file_name']);
                            Log::info('Document added to ZIP', [
                                'file_path' => $doc['file_path'],
                                'zip_path' => "dokumen/{$userFolder}/" . $doc['new_file_name']
                            ]);
                        } else {
                            Log::warning('Document file not found', [
                                'file_path' => $doc['file_path'] ?? 'null',
                                'document' => $doc
                            ]);
                        }
                    }
                }
            }
            
            // Add README file
            $readme = "BERSEKOLAH DATA EXPORT\n";
            $readme .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
            $readme .= "Tables included: " . implode(', ', $tables) . "\n\n";
            $readme .= "Structure:\n";
            if (in_array('data_beswan', $tables)) {
                $readme .= "- data_beswan.xlsx: Data lengkap beswan yang telah mendaftar beasiswa\n";
                $readme .= "  * Data Pribadi: nama, tempat/tanggal lahir, jenis kelamin, agama, kontak\n";
                $readme .= "  * Alamat: alamat lengkap, RT/RW, kelurahan, kecamatan, kabupaten, provinsi\n";
                $readme .= "  * Data Keluarga: nama & pekerjaan ayah/ibu, penghasilan, jumlah saudara\n";
                $readme .= "  * Data Pendidikan: asal sekolah, daerah, jurusan, tingkat kelas\n";
                $readme .= "  * Status Aplikasi: application_id, status diterima\n\n";
            }
            if (in_array('dokumen_beswan', $tables)) {
                $readme .= "- dokumen/: Folder berisi dokumen-dokumen beswan\n";
                $readme .= "  * Setiap folder dinamai sesuai nama user\n";
                $readme .= "  * File didalam folder dinamai: [nama_user]_[jenis_dokumen].[ekstensi]\n";
                $readme .= "  * Contoh: azzam_essay.pdf, john_ktp.jpg\n";
                $readme .= "  * Dokumen diambil dari storage/app/public/\n";
            }
            
            $zip->addFromString('README.txt', $readme);
            Log::info('README added to ZIP');
            
            $zip->close();
            Log::info('ZIP file closed successfully');
            
            return response()->download($zipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                'Cache-Control' => 'max-age=0',
                'Pragma' => 'public',
                'Expires' => '0'
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            $zip->close();
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            throw $e;
        }
    }
    
    /**
     * Create Excel content for ZIP
     *
     * @param array $data
     * @param string $tableName
     * @return string
     */
    private function createExcelContent($data, $tableName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($tableName);
        
        if (!empty($data)) {
            // Get headers from first row
            $firstRow = $data[0];
            $headers = array_keys($firstRow);
            
            // Set headers
            $colIndex = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($colIndex . '1', $header);
                $sheet->getStyle($colIndex . '1')->getFont()->setBold(true);
                $colIndex++;
            }
            
            // Add data
            $rowCount = 2;
            foreach ($data as $row) {
                $colIndex = 'A';
                foreach ($row as $value) {
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value);
                    } elseif ($value === null) {
                        $value = '';
                    }
                    $sheet->setCellValue($colIndex . $rowCount, $value);
                    $colIndex++;
                }
                $rowCount++;
            }
        }
        
        // Save to string
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);
        
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    }
    
    /**
     * Validate table and format combination
     *
     * @param array $tables
     * @param string $format
     * @return bool|string
     */
    private function validateTableFormatCombination(array $tables, string $format)
    {
        // Dokumen beswan hanya bisa ZIP
        if (in_array('dokumen_beswan', $tables) && $format !== 'zip') {
            return 'Dokumen beswan hanya tersedia dalam format ZIP';
        }
        
        // Data beswan tidak bisa ZIP
        if (in_array('data_beswan', $tables) && $format === 'zip') {
            return 'Data beswan tidak tersedia dalam format ZIP. Gunakan Excel, CSV, atau JSON';
        }
        
        // Jika ada dokumen_beswan, pastikan tidak ada table lain yang tidak cocok dengan ZIP
        if (in_array('dokumen_beswan', $tables) && $format === 'zip') {
            $incompatibleTables = array_diff($tables, ['dokumen_beswan']);
            if (!empty($incompatibleTables)) {
                return 'Format ZIP hanya mendukung dokumen_beswan saja';
            }
        }
        
        return true;
    }
}
