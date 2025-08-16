<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Beswan;
use App\Models\CalonBeswan;
use App\Models\BeasiswaPeriods;
use App\Models\BeasiswaApplication;
use App\Models\BeasiswaRecipients;
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
{
    /**
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
            'format' => 'required|string|in:csv,excel,json',
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

            // Collect data from selected tables
            $exportData = $this->collectData($tables, $dateRange);
            
            // Generate export file based on format
            switch ($format) {
                case 'excel':
                    // Ensure Excel format is handled correctly
                    return $this->exportToExcel($exportData, $tables);
                case 'csv':
                    return $this->exportToCsv($exportData, $tables);
                case 'json':
                    return $this->exportToJson($exportData);
                default:
                    return response()->json(['message' => 'Format tidak didukung'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Export data error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengekspor data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Collect data from selected tables based on date range
     *
     * @param array $tables
     * @param string $dateRange
     * @return array
     */
    private function collectData(array $tables, string $dateRange)
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
                            // Tambahkan lebih banyak kolom yang relevan
                            $exportData[$table] = $query->select(
                                'id', 'name', 'email', 'role', 'status', 'email_verified_at', 'created_at', 'updated_at'
                            )->get();
                            break;

                        case 'calon_beswans':
                            $query = CalonBeswan::query();
                            if ($dateRange !== 'all') {
                                $query->whereBetween('created_at', [$dateFilter['start'], $dateFilter['end']]);
                            }
                            // Tambahkan informasi terkait
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
                                'dokumenBeswan',
                                'periode'
                            ])->get();
                            
                            // Buat data yang lebih lengkap dan flat
                            $enrichedBeswans = $beswans->map(function($beswan) {
                                $data = $beswan->toArray();
                                
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
                            $exportData[$table] = $query->with(['user', 'calonBeswan', 'periode'])->get();
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
                    if (empty($exportData[$table]) || !$exportData[$table]->count()) {
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
                    'start' => Carbon::minValue(),
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
     */
    private function exportToExcel(array $data, array $tables)
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
                        $rowData = $data[$tableName][0]->toArray();
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
                            $rowArray = $row->toArray();
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
     */
    private function exportToCsv(array $data, array $tables)
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
}
