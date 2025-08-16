<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaSosial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaSosialController extends Controller
{
    /**
     * Display the media sosial settings.
     */
    public function index(): JsonResponse
    {
        try {
            $mediaSosial = MediaSosial::first();
            
            if (!$mediaSosial) {
                // Create default entry if none exists
                $mediaSosial = MediaSosial::create([
                    'link_grup_beasiswa' => 'https://chat.whatsapp.com/DBWgEhlvkz3E0SqpdvIL1q'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $mediaSosial,
                'message' => 'Media sosial settings retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media sosial settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the media sosial settings.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'twibbon_link' => 'nullable|url|max:2048',
                'instagram_link' => 'nullable|url|max:2048',
                'link_grup_beasiswa' => 'nullable|url|max:2048',
                'whatsapp_number' => 'nullable|string|max:20',
                'judul_essay' => 'nullable|string|max:255',
            ]);

            $mediaSosial = MediaSosial::first();
            
            if (!$mediaSosial) {
                $mediaSosial = new MediaSosial();
            }

            $mediaSosial->fill($request->only([
                'twibbon_link',
                'instagram_link',
                'link_grup_beasiswa',
                'whatsapp_number',
                'judul_essay'
            ]));
            
            $mediaSosial->save();

            return response()->json([
                'success' => true,
                'data' => $mediaSosial,
                'message' => 'Media sosial settings updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media sosial settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public media sosial settings (for public access).
     */
    public function public(): JsonResponse
    {
        try {
            $mediaSosial = MediaSosial::first();
            
            if (!$mediaSosial) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'link_grup_beasiswa' => 'https://chat.whatsapp.com/DBWgEhlvkz3E0SqpdvIL1q'
                    ],
                    'message' => 'Default media sosial settings'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $mediaSosial,
                'message' => 'Media sosial settings retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media sosial settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
