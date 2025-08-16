<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

// Quick setup endpoint untuk production
Route::get('/setup/storage-link', function () {
    try {
        // Attempt to create storage link
        $result = Artisan::call('storage:link');
        
        return response()->json([
            'success' => true,
            'artisan_result' => $result,
            'public_storage_exists' => file_exists(public_path('storage')),
            'is_link' => is_link(public_path('storage')),
            'link_target' => is_link(public_path('storage')) ? readlink(public_path('storage')) : null,
            'storage_app_public_exists' => file_exists(storage_path('app/public')),
            'message' => 'Storage link setup completed'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'public_storage_exists' => file_exists(public_path('storage')),
            'is_link' => is_link(public_path('storage')),
            'storage_app_public_exists' => file_exists(storage_path('app/public')),
        ], 500);
    }
});

// Test foto mentor langsung
Route::get('/test/mentor-photo/{filename}', function ($filename) {
    $path = storage_path("app/public/admin/mentor/{$filename}");
    
    if (File::exists($path)) {
        return response()->file($path);
    }
    
    return response()->json([
        'error' => 'File not found',
        'path' => $path,
        'filename' => $filename,
        'storage_exists' => File::exists(storage_path('app/public')),
        'admin_folder_exists' => File::exists(storage_path('app/public/admin')),
        'mentor_folder_exists' => File::exists(storage_path('app/public/admin/mentor')),
    ], 404);
});

// Test environment
Route::get('/test/env', function () {
    return response()->json([
        'APP_ENV' => env('APP_ENV'),
        'APP_URL' => env('APP_URL'),
        'config_app_env' => config('app.env'),
        'config_app_url' => config('app.url'),
        'is_production' => env('APP_ENV') === 'production',
        'has_railway_url' => str_contains(env('APP_URL', ''), 'railway'),
        'mentor_model_test' => function() {
            $mentor = \App\Models\Mentor::first();
            return $mentor ? $mentor->photo_url : 'No mentor found';
        }
    ]);
});
