<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $announcements
        ]);
    }
    
    /**
     * Display a listing of the published announcements.
     */
    public function getPublishedAnnouncements()
    {
        $announcements = Announcement::published()->get();
        
        return response()->json([
            'success' => true,
            'data' => $announcements
        ])->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'
        ]);
    }

    /**
     * Display a listing of the published announcements with debug info.
     * This is a public endpoint that should not require authentication.
     */
    public function getPublishedAnnouncementsDebug()
    {
        $announcements = Announcement::published()->get();
        
        // Add debug information to help troubleshoot API connectivity issues
        return response()->json([
            'success' => true,
            'data' => $announcements,
            'debug' => [
                'timestamp' => now()->toDateTimeString(),
                'source' => 'Laravel API',
                'auth_check' => auth()->check() ? 'authenticated' : 'not authenticated',
                'request_headers' => collect(request()->headers->all())
                    ->map(fn($header) => is_array($header) ? implode(', ', $header) : $header)
                    ->toArray(),
                'server_info' => [
                    'php_version' => phpversion(),
                    'laravel_version' => app()->version()
                ]
            ]
        ])->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'X-Debug-Mode' => 'enabled'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * Not used in API context.
     */
    public function create()
    {
        // Not used in API context
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'tag' => 'nullable|string|max:100',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Set published_at to current date if status is published and published_at is not provided
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        
        $announcement = Announcement::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Announcement created successfully',
            'data' => $announcement
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $announcement = Announcement::find($id);
        
        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $announcement
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * Not used in API context.
     */
    public function edit(string $id)
    {
        // Not used in API context
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $announcement = Announcement::find($id);
        
        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:draft,published,archived',
            'tag' => 'nullable|string|max:100',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Set published_at to current date if status changed to published and published_at is null
        if (isset($data['status']) && $data['status'] === 'published' && 
            $announcement->status !== 'published' && empty($announcement->published_at)) {
            $data['published_at'] = now();
        }
        
        $announcement->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Announcement updated successfully',
            'data' => $announcement
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $announcement = Announcement::find($id);
        
        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found'
            ], 404);
        }

        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully',
        ]);
    }
    
    /**
     * Update the status of an announcement.
     */
    public function updateStatus(Request $request, string $id)
    {
        $announcement = Announcement::find($id);
        
        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = $request->status;
        
        // Set published_at to now if status changed to published and published_at is not set
        if ($status === 'published' && $announcement->status !== 'published' && empty($announcement->published_at)) {
            $announcement->published_at = now();
        }
        
        $announcement->status = $status;
        $announcement->save();

        return response()->json([
            'success' => true,
            'message' => 'Announcement status updated successfully',
            'data' => $announcement
        ]);
    }

    /**
     * Mark an announcement as read by the current user.
     */
    public function markAsRead(string $id)
    {
        $announcement = Announcement::find($id);
        
        if (!$announcement) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found'
            ], 404);
        }

        // Create or update the read record
        AnnouncementRead::updateOrCreate(
            [
                'announcement_id' => $id,
                'user_id' => Auth::id()
            ],
            [
                'read_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement marked as read'
        ]);
    }

    /**
     * Get all announcements with read status for the current user.
     */
    public function getAnnouncementsWithReadStatus()
    {
        $user = Auth::user();
        $announcements = Announcement::published()->get();
        
        foreach ($announcements as $announcement) {
            $readRecord = AnnouncementRead::where('announcement_id', $announcement->id)
                ->where('user_id', $user->id)
                ->first();
                
            $announcement->is_read = !is_null($readRecord);
            $announcement->read_at = $readRecord ? $readRecord->read_at : null;
        }
        
        return response()->json([
            'success' => true,
            'data' => $announcements
        ]);
    }
}
