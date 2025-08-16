<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnnouncementController;

/*
|--------------------------------------------------------------------------
| API Read Tracking Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for tracking reads of various content
|
*/

// Announcement read tracking routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/announcements/{id}/mark-as-read', [AnnouncementController::class, 'markAsRead']);
    Route::get('/announcements/with-read-status', [AnnouncementController::class, 'getAnnouncementsWithReadStatus']);
});
