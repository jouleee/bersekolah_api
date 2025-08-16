<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

Route::get('/debug/files/{folder?}', function ($folder = null) {
    $basePath = storage_path('app/public');
    $results = [
        'base_path' => $basePath,
        'base_exists' => File::exists($basePath),
    ];
    
    if ($folder) {
        $folderPath = $basePath . '/admin/' . $folder;
        $results['folder_path'] = $folderPath;
        $results['folder_exists'] = File::exists($folderPath);
        
        if (File::exists($folderPath)) {
            $files = File::files($folderPath);
            $results['files'] = array_map(fn($file) => $file->getFilename(), $files);
            $results['file_count'] = count($files);
        }
    } else {
        // List admin folders
        $adminPath = $basePath . '/admin';
        if (File::exists($adminPath)) {
            $folders = File::directories($adminPath);
            $results['admin_folders'] = array_map(fn($dir) => basename($dir), $folders);
        }
    }
    
    return response()->json($results);
});

Route::get('/debug/env', function () {
    return response()->json([
        'APP_ENV' => env('APP_ENV'),
        'APP_URL' => env('APP_URL'),
        'config_app_env' => config('app.env'),
        'config_app_url' => config('app.url'),
        'is_railway' => str_contains(env('APP_URL', ''), 'railway'),
        'should_use_https' => env('APP_ENV') === 'production' || str_contains(env('APP_URL', ''), 'railway'),
    ]);
});

Route::get('/debug/storage-link', function () {
    try {
        // Attempt to create storage link
        $result = \Illuminate\Support\Facades\Artisan::call('storage:link');
        
        return response()->json([
            'success' => true,
            'result' => $result,
            'public_storage_exists' => file_exists(public_path('storage')),
            'is_link' => is_link(public_path('storage')),
            'link_target' => is_link(public_path('storage')) ? readlink(public_path('storage')) : null,
            'storage_app_public_exists' => file_exists(storage_path('app/public')),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'public_storage_exists' => file_exists(public_path('storage')),
            'is_link' => is_link(public_path('storage')),
            'storage_app_public_exists' => file_exists(storage_path('app/public')),
        ]);
    }
});

Route::get('/debug/storage', function () {
    $info = [
        'storage_path' => storage_path(),
        'public_path' => public_path(),
        'storage_public_path' => storage_path('app/public'),
        'public_storage_path' => public_path('storage'),
        'storage_link_exists' => is_link(public_path('storage')),
        'storage_dir_exists' => File::exists(public_path('storage')),
        'app_public_exists' => File::exists(storage_path('app/public')),
        'admin_folders' => [],
        'sample_files' => []
    ];

    // Check admin folders
    $adminPath = storage_path('app/public/admin');
    if (File::exists($adminPath)) {
        $info['admin_folders'] = File::directories($adminPath);
        
        // Check for sample files in each admin folder
        foreach (['mentor', 'testimoni', 'artikel'] as $folder) {
            $folderPath = $adminPath . '/' . $folder;
            if (File::exists($folderPath)) {
                $files = File::files($folderPath);
                $info['sample_files'][$folder] = array_slice(
                    array_map(fn($file) => $file->getFilename(), $files), 
                    0, 5
                ); // First 5 files
            }
        }
    }

    // Check if symlink target is correct
    if (is_link(public_path('storage'))) {
        $info['symlink_target'] = readlink(public_path('storage'));
    }

    return response()->json($info, 200, [], JSON_PRETTY_PRINT);
});

Route::get('/debug/test-image/{folder}/{filename}', function ($folder, $filename) {
    $filePath = storage_path("app/public/admin/{$folder}/{$filename}");
    
    if (File::exists($filePath)) {
        return response()->file($filePath);
    }
    
    return response()->json([
        'error' => 'File not found',
        'path' => $filePath,
        'exists' => false
    ], 404);
});
