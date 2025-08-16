<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FaqController;

// Include debug routes for storage testing
require __DIR__ . '/debug-storage.php';

// Include quick setup routes for production
require __DIR__ . '/quick-setup.php';
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeswanController;
use App\Http\Controllers\TestimoniController;
use App\Http\Controllers\UploadTypeController;
use App\Http\Controllers\CalonBeswanController;
use App\Http\Controllers\BesWanDocumentController;
use App\Http\Controllers\BeasiswaPeriodsController;
use App\Http\Controllers\AdditionalUploadController;
use App\Http\Controllers\KontenBersekolahController;
use App\Http\Controllers\BerkasCalonBeswanController;
use App\Http\Controllers\BeasiswaRecipientsController;
use App\Http\Controllers\BeasiswaApplicationController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ExportDataController;
use App\Http\Controllers\BeasiswaCountdownController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Log;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// API Health Check
Route::get('/', function () {
    return response()->json([
        'version' => '1.0.0'
    ]);
});

// Media Sosial links (public access)
Route::get('/media-sosial/latest', [App\Http\Controllers\MediaSosialController::class, 'getLatest']);
Route::get('/media-sosial/public', [App\Http\Controllers\Api\Admin\MediaSosialController::class, 'public']);

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);  
Route::post('/login', [AuthController::class, 'login']);

// Password Management Routes
Route::post('/change-password', [App\Http\Controllers\UserPasswordController::class, 'update'])->middleware('auth:sanctum');
Route::post('/update-password', [App\Http\Controllers\UserPasswordController::class, 'update'])->middleware('auth:sanctum');

// Dashboard Public Stats
Route::get('/dashboard/quick-actions', [DashboardController::class, 'getQuickActionStats']);
Route::get('/applications/statistics', [DashboardController::class, 'getApplicationStats']);
Route::get('/recent-activities', [DashboardController::class, 'getRecentActivities']);
Route::get('/dashboard/consolidated-stats', [DashboardController::class, 'getConsolidatedStats']);
Route::get('/documents/statistics', [BesWanDocumentController::class, 'getDocumentStatistics']);

// Media Sosial
Route::get('/media-sosial/latest', [App\Http\Controllers\MediaSosialController::class, 'getLatest']);
Route::get('/periods/stats', [DashboardController::class, 'getPeriodStats']);

// Public Content Routes
Route::get('/testimoni', [App\Http\Controllers\TestimoniController::class, 'index']);
Route::get('/testimoni/{id}', [App\Http\Controllers\TestimoniController::class, 'show']);
Route::get('/faq', [App\Http\Controllers\FaqController::class, 'index']);
Route::get('/faq/{id}', [App\Http\Controllers\FaqController::class, 'show']);
Route::get('/konten', [App\Http\Controllers\KontenBersekolahController::class, 'index']);
Route::get('/konten/{id}', [App\Http\Controllers\KontenBersekolahController::class, 'show']);

// Public Announcement Routes - Explicitly disable auth middleware and ensure CORS access
Route::get('/announcements', [AnnouncementController::class, 'getPublishedAnnouncements'])->withoutMiddleware(['auth', 'auth:sanctum']);
Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->withoutMiddleware(['auth', 'auth:sanctum']);

// Options route for CORS preflight requests
Route::options('/announcements', function() {
    return response()->json([], 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization'
    ]);
});

Route::options('/announcements/{id}', function() {
    return response()->json([], 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization'
    ]);
});

// Public Periods Route - No authentication needed
Route::get('/public/beasiswa-periods', [BeasiswaPeriodsController::class, 'getPublicPeriods'])->withoutMiddleware(['auth', 'auth:sanctum']);

// Public Document Types - No authentication needed for frontend to fetch available document types
Route::get('/document-types', [BesWanDocumentController::class, 'getDocumentTypes'])->withoutMiddleware(['auth', 'auth:sanctum']);

Route::options('/public/beasiswa-periods', function() {
    return response()->json([], 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization'
    ]);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // User management routes for admin and superadmin
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/users', [AuthController::class, 'getUsers']);
        Route::post('/users', [AuthController::class, 'createUser']);
        Route::put('/users/{id}', [AuthController::class, 'updateUser']);
        Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
    });

    /*
    |--------------------------------------------------------------------------
    | Beswan Routes
    |--------------------------------------------------------------------------
    */
    
    // Get all beswan
    Route::get('/beswan', [App\Http\Controllers\BeswanController::class, 'index']);
    
    // Get accepted beswan with search
    Route::get('/beswan/accepted', [App\Http\Controllers\BeswanController::class, 'getAcceptedBeswan']);
    
    // Get single beswan by ID
    Route::get('/beswan/{id}', [App\Http\Controllers\BeswanController::class, 'show']);
    
    // Update beswan
    Route::put('/beswan/{id}', [App\Http\Controllers\BeswanController::class, 'update']);
    
    // Delete beswan
    Route::delete('/beswan/{id}', [App\Http\Controllers\BeswanController::class, 'destroy']);
    
    // Reject beswan (change status to rejected)
    Route::patch('/beswan/{id}/reject', [App\Http\Controllers\BeswanController::class, 'rejectBeswan']);

    Route::get('/beswan/count', [BeswanController::class, 'count']);


    // Atau bisa juga menggunakan Resource Route untuk lebih singkat
    Route::apiResource('beswan', App\Http\Controllers\BeswanController::class);
});

Route::middleware('auth:sanctum')->group(function () {

    // Authentication Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/admin/profile', [AuthController::class, 'updateProfile']);
    Route::post('/admin/change-password', [App\Http\Controllers\UserPasswordController::class, 'update']);
    
    // Password Management Routes
    Route::post('/password/update', [App\Http\Controllers\UserPasswordController::class, 'update']);
    Route::post('/password', [App\Http\Controllers\UserPasswordController::class, 'update']);
    Route::post('/users/password', [App\Http\Controllers\UserPasswordController::class, 'update']);
    Route::post('/user/password', [App\Http\Controllers\UserPasswordController::class, 'update']);
    Route::post('/profile/password', [App\Http\Controllers\UserPasswordController::class, 'update']);
    
    // User Data Routes
    Route::post('/calon-beswan/pribadi', [App\Http\Controllers\CalonBeswanController::class, 'postPribadi']);
    Route::get('/calon-beswan/pribadi', [App\Http\Controllers\CalonBeswanController::class, 'getPribadi']);

    // Family Data Routes
    Route::post('/calon-beswan/keluarga', [App\Http\Controllers\CalonBeswanController::class, 'postKeluarga']);
    Route::get('/calon-beswan/keluarga', [App\Http\Controllers\CalonBeswanController::class, 'getKeluarga']);
    
    // Address Data Routes
    Route::post('/calon-beswan/alamat', [App\Http\Controllers\CalonBeswanController::class, 'postAlamat']);
    Route::get('/calon-beswan/alamat', [App\Http\Controllers\CalonBeswanController::class, 'getAlamat']);
    
    /*
    |--------------------------------------------------------------------------
    | Document Management Routes - SCHEMA BARU (BesWanDocumentController)
    |--------------------------------------------------------------------------
    */
    
    // Get User's Uploaded Documents
    Route::get('/my-documents', [BesWanDocumentController::class, 'getDokumenWajib']);
    
    // Upload Endpoints - Dokumen Wajib
    Route::post('/upload-bukti-status-siswa', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'student_proof');
    });
    
    Route::post('/upload-identitas-diri', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'identity_proof');
    });
    
    Route::post('/upload-foto-diri', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'photo');
    });
    
    // Upload Endpoints - Sosial Media
    Route::post('/upload-bukti-follow', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'instagram_follow');
    });
    
    Route::post('/upload-twibon', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'twibbon_post');
    });
    
    // Upload Endpoints - Dokumen Pendukung
    Route::post('/upload-sertifikat-prestasi', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'achievement_certificate');
    });
    
    Route::post('/upload-surat-rekomendasi', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'recommendation_letter');
    });
    
    Route::post('/upload-essay-motivasi', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'essay_motivation');
    });
    
    Route::post('/upload-cv-resume', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'cv_resume');
    });
    
    Route::post('/upload-dokumen-lainnya', function(Request $request) {
        return app(BesWanDocumentController::class)->uploadDocument($request, 'other_document');
    });
    
    // Generic Upload Endpoint
    Route::post('/upload-document/{documentCode}', [BesWanDocumentController::class, 'uploadDocument']);
    
    // Delete Document
    Route::delete('/documents/{documentId}', [BesWanDocumentController::class, 'deleteDocument']);
    
    /*
    |--------------------------------------------------------------------------
    | Other Resource Routes
    |--------------------------------------------------------------------------
    */
    
    Route::apiResource('berkas-calon-beswan', BerkasCalonBeswanController::class);
    Route::apiResource('upload-type', UploadTypeController::class);
    Route::apiResource('additional-upload', AdditionalUploadController::class);
    Route::apiResource('beasiswa-applications', BeasiswaApplicationController::class);
    Route::apiResource('beasiswa-periods', BeasiswaPeriodsController::class);
    Route::patch('/beasiswa-periods/{id}/toggle-active', [App\Http\Controllers\BeasiswaPeriodsController::class, 'toggleActive']);
    Route::apiResource('beasiswa-recipients', BeasiswaRecipientsController::class);
    
    /*
    |--------------------------------------------------------------------------
    | Admin Only Routes
    |--------------------------------------------------------------------------
    */
    
    Route::middleware('role:admin,superadmin')->group(function () {
        // Document Management Admin
        Route::patch('/documents/{documentId}/status', [BesWanDocumentController::class, 'updateStatus']);
        Route::get('/admin/documents/{category?}', [BesWanDocumentController::class, 'getDocumentsByCategory']);
        Route::get('/admin/documents-statistics', [BesWanDocumentController::class, 'getDocumentStatistics']);
        
        // Content Management Admin
        // Route::apiResource('admin-testimonial', App\Http\Controllers\TestimonialController::class);
        Route::apiResource('admin-faq', App\Http\Controllers\FaqController::class)->except(['index', 'show']);
        Route::apiResource('admin-konten', App\Http\Controllers\KontenBersekolahController::class);
        Route::get('/admin-konten/all', [App\Http\Controllers\KontenBersekolahController::class, 'all']);
        Route::get('/admin-konten-all', [App\Http\Controllers\KontenBersekolahController::class, 'all']);
        Route::patch('/admin-konten/{id}/status', [App\Http\Controllers\KontenBersekolahController::class, 'updateStatus']);
        
        // Testimoni Management Admin
        Route::post('/testimoni', [App\Http\Controllers\TestimoniController::class, 'store']);
        Route::put('/testimoni/{id}', [App\Http\Controllers\TestimoniController::class, 'update']);
        Route::delete('/testimoni/{id}', [App\Http\Controllers\TestimoniController::class, 'destroy']);
        Route::put('/testimoni/{id}/status', [App\Http\Controllers\TestimoniController::class, 'updateStatus']);
        
        // Announcement Management
        Route::apiResource('announcements', AnnouncementController::class);
        Route::patch('/announcements/{id}/status    ', [AnnouncementController::class, 'updateStatus']);
        
        // Announcement Read Tracking
        Route::post('/announcements/{id}/mark-as-read', [AnnouncementController::class, 'markAsRead']);
        Route::get('/announcements/with-read-status', [AnnouncementController::class, 'getAnnouncementsWithReadStatus']);
    });

    Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->group(function () {
        // Document Management Admin Routes
        Route::get('/admin/documents/{category?}', [BesWanDocumentController::class, 'getDocumentsByCategory']);
        Route::patch('/documents/{documentId}/status', [BesWanDocumentController::class, 'updateStatus']);
        Route::get('/admin/documents-statistics', [BesWanDocumentController::class, 'getDocumentStatistics']);
    });
});

// Beasiswa Application routes
Route::middleware('auth:sanctum')->group(function () {
    // Finalize application
    Route::post('/finalize-application', [App\Http\Controllers\BeasiswaApplicationController::class, 'finalizeApplication']);
    Route::get('/application-status', [App\Http\Controllers\BeasiswaApplicationController::class, 'getApplicationStatus']);
    Route::get('/check-finalization-eligibility', [App\Http\Controllers\BeasiswaApplicationController::class, 'checkFinalizationEligibility']);
});

// Admin Beasiswa Application routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Beasiswa Applications Management
    Route::get('/applications', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'index']);
    Route::get('/applications/statistics', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'statistics']);
    Route::get('/applications/{id}', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'show']);
    Route::patch('/applications/{id}/status', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'updateStatus']);
    Route::post('/applications/bulk-update-status', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'bulkUpdateStatus']);
    Route::post('/applications/{id}/interview', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'setInterviewSchedule']);
});

// Additional route for checking edit status
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/documents/edit-status', [App\Http\Controllers\BesWanDocumentController::class, 'getEditStatus']);
});

// Ini buat Countdown Timer 
Route::get('/countdown/pendaftaran', [BeasiswaCountdownController::class, 'countdown']);

// Mentor CRUD & total
Route::get('/mentors', [MentorController::class, 'index']);
Route::get('/mentors/total', [MentorController::class, 'total']);
Route::post('/mentors', [MentorController::class, 'store']);
Route::get('/mentors/{id}', [MentorController::class, 'show']);
Route::put('/mentors/{id}', [MentorController::class, 'update']);
Route::delete('/mentors/{id}', [MentorController::class, 'destroy']);

// Dashboard Routes
Route::prefix('dashboard')->group(function() {
    Route::get('/quick-actions', [DashboardController::class, 'getQuickActionStats']);
    Route::get('/recent-activities', [DashboardController::class, 'getRecentActivities']);
});

// Ngirim pesan ke Email
Route::get('/form-kontak', [ContactController::class, 'showForm']);
Route::post('/form-kontak', [ContactController::class, 'sendEmail'])->name('form.send');
Route::post('/kirim-pesan', [ContactController::class, 'kirimPesan']);

// Untuk kebutuhan export data
Route::get('/export', [ExportDataController::class, 'export'])->middleware(['auth:sanctum', 'role:admin,superadmin']);

// Public routes
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth routes
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth:sanctum');

// Public content routes
// Route::prefix('public')->group(function () {
//     Route::get('/settings', [App\Http\Controllers\PublicContentController::class, 'getSettings']);
//     Route::get('/faqs', [App\Http\Controllers\PublicContentController::class, 'getFaqs']);
//     Route::get('/testimonials', [App\Http\Controllers\PublicContentController::class, 'getTestimonials']);
// });

// Protected routes untuk calon beswan
Route::middleware('auth:sanctum')->group(function () {
    
    // Profile routes
    Route::prefix('calon-beswan')->group(function () {
        Route::get('/pribadi', [App\Http\Controllers\CalonBeswanController::class, 'getPribadi']);
        Route::post('/pribadi', [App\Http\Controllers\CalonBeswanController::class, 'postPribadi']);
        
        Route::get('/keluarga', [App\Http\Controllers\CalonBeswanController::class, 'getKeluarga']);
        Route::post('/keluarga', [App\Http\Controllers\CalonBeswanController::class, 'postKeluarga']);
        
        Route::get('/alamat', [App\Http\Controllers\CalonBeswanController::class, 'getAlamat']);
        Route::post('/alamat', [App\Http\Controllers\CalonBeswanController::class, 'postAlamat']);
    });

    // Document management routes
    Route::prefix('documents')->group(function () {
        Route::post('/upload', [App\Http\Controllers\BesWanDocumentController::class, 'store']);
        // ✅ FIXED: Gunakan BesWanDocumentController::class, bukan CalonBeswanController
        Route::get('/my-documents', [App\Http\Controllers\BesWanDocumentController::class, 'getDokumenWajib']);
        Route::delete('/{id}', [App\Http\Controllers\BesWanDocumentController::class, 'destroy']);
    });

    // ✅ Beasiswa Application routes - TAMBAHAN BARU
    Route::prefix('beasiswa')->group(function () {
        // Finalize application
        Route::post('/finalize-application', [App\Http\Controllers\BeasiswaApplicationController::class, 'finalizeApplication']);
        
        // Get application status
        Route::get('/application-status', [App\Http\Controllers\BeasiswaApplicationController::class, 'getApplicationStatus']);
        
        // Check finalization eligibility
        Route::get('/check-finalization-eligibility', [App\Http\Controllers\BeasiswaApplicationController::class, 'checkFinalizationEligibility']);
        
        // Get active periods
        Route::get('/periods', [App\Http\Controllers\BeasiswaPeriodsController::class, 'index']);
    });

});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->prefix('admin')->group(function () {
    
    // Media Sosial management
    Route::get('/media-sosial', [App\Http\Controllers\Api\Admin\MediaSosialController::class, 'index']);
    Route::put('/media-sosial', [App\Http\Controllers\Api\Admin\MediaSosialController::class, 'update']);
    
    // Content management
    Route::apiResource('settings', App\Http\Controllers\SettingController::class);
    Route::apiResource('faqs', App\Http\Controllers\FAQController::class);  
    
    Route::apiResource('media-sosial', App\Http\Controllers\MediaSosialController::class);
    
    // Export data functionality
    Route::post('/export-data', [ExportDataController::class, 'export']);
    
    // Route::get('/users', [App\Http\Controllers\AdminController::class, 'getUsers']);
    // Route::get('/users/{id}', [App\Http\Controllers\AdminController::class, 'getUserDetail']);
    // Route::put('/users/{id}/role', [App\Http\Controllers\AdminController::class, 'updateUserRole']);
    // Route::delete('/users/{id}', [App\Http\Controllers\AdminController::class, 'deleteUser']);
    
    // Beasiswa management
    Route::apiResource('beasiswa-periods', App\Http\Controllers\BeasiswaPeriodsController::class);
    
    // Document management
    Route::prefix('documents')->group(function () {
        Route::get('/', [App\Http\Controllers\BesWanDocumentController::class, 'index']);
        Route::put('/{id}/verify', [App\Http\Controllers\BesWanDocumentController::class, 'verify']);
        Route::put('/{id}/reject', [App\Http\Controllers\BesWanDocumentController::class, 'reject']);
        Route::get('/admin/documents-statistics', [App\Http\Controllers\BesWanDocumentController::class, 'getDocumentStatistics']);
    });

    // ✅ Beasiswa Applications Management - TAMBAHAN BARU
    Route::prefix('applications')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'index']);
        Route::get('/statistics', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'statistics']);
        Route::get('/{id}', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'show']);
        Route::patch('/{id}/status', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'updateStatus']);
        
        // Bulk update statuses
        Route::post('/bulk-update-status', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'bulkUpdateStatus']);
        
        // Comprehensive review (with interview setup)
        Route::patch('/{id}/review', [App\Http\Controllers\AdminBeasiswaApplicationController::class, 'reviewApplication']);
    });
});

// Public FAQ routes
Route::get('/faqs', [FaqController::class, 'index']); // Get published FAQs

// Admin FAQ routes (protected)
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::apiResource('faqs', FaqController::class); // Full CRUD for admin
});

// Export Data route for admin/superadmin
Route::post('/export-data', [ExportDataController::class, 'export'])
    ->middleware(['auth:sanctum', 'role:admin,superadmin']);

// Dashboard routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/quick-actions', [DashboardController::class, 'quickActions']);
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics']);
    Route::get('/dashboard/recent-activities', [DashboardController::class, 'getRecentActivities']);
});

// // Diagnostic endpoint
// Route::get('/debug/cors-test', function() {
//     return response()->json([
//         'success' => true,
//         'message' => 'CORS test endpoint working',
//         'timestamp' => now()->toDateTimeString(),
//         'server_info' => [
//             'php_version' => phpversion(),
//             'laravel_version' => app()->version(),
//             'environment' => app()->environment(),
//         ],
//         'headers' => collect(request()->headers->all())
//             ->map(fn($header) => is_array($header) ? implode(', ', $header) : $header)
//             ->toArray(),
//     ])->withHeaders([
//         'Access-Control-Allow-Origin' => '*',
//         'Access-Control-Allow-Methods' => 'GET, OPTIONS',
//         'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization',
//         'X-Debug-Cors' => 'enabled'
//     ]);
// });

Route::options('/debug/cors-test', function() {
    return response()->json([], 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization',
        'X-Debug-Cors' => 'enabled'
    ]);
});

// Debug announcement route (explicitly disable auth)
Route::get('/debug/announcements', [AnnouncementController::class, 'getPublishedAnnouncementsDebug'])
    ->withoutMiddleware(['auth', 'auth:sanctum']);

Route::options('/debug/announcements', function() {
    return response()->json([], 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization'
    ]);
});

Route::middleware(['auth:sanctum', 'role:admin,superadmin'])->get('/admin/testimoni', [App\Http\Controllers\TestimoniController::class, 'index']);

// Test endpoint untuk debug export
Route::get('/test-export', function(Request $request) {
    try {
        Log::info('Test export endpoint called');
        
        // Test basic database connection
        $applications = \App\Models\BeasiswaApplication::with('beswan.user')->get();
        Log::info('Found applications', ['count' => $applications->count()]);
        
        // Test document retrieval
        $documents = \App\Models\BeswanDocument::all();
        Log::info('Found documents', ['count' => $documents->count()]);
        
        return response()->json([
            'status' => 'success',
            'applications_count' => $applications->count(),
            'documents_count' => $documents->count(),
            'storage_path' => storage_path('app/public'),
            'storage_exists' => file_exists(storage_path('app/public'))
        ]);
        
    } catch (\Exception $e) {
        Log::error('Test export error', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware(['auth:sanctum', 'role:admin,superadmin']);

