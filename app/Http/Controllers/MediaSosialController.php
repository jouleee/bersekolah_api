<?php

namespace App\Http\Controllers;

use App\Models\MediaSosial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaSosialController extends Controller
{    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('latest') && $request->latest) {
            // Return only the latest entry
            $mediaSosial = MediaSosial::latest()->take(1)->get();
        } else {
            // Return all entries
            $mediaSosial = MediaSosial::all();
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $mediaSosial
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'twibbon_link' => 'nullable|url|max:2048',
            'instagram_link' => 'nullable|url|max:2048',
            'link_grup_beasiswa' => 'nullable|url|max:2048',
            'whatsapp_number' => 'nullable|string|max:20',
            'judul_essay' => 'nullable|string|max:255',
        ]);

        $mediaSosial = MediaSosial::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Media sosial berhasil ditambahkan',
            'data' => $mediaSosial
        ], 201);
    }    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $mediaSosial = MediaSosial::findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $mediaSosial
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'twibbon_link' => 'nullable|url|max:2048',
            'instagram_link' => 'nullable|url|max:2048',
            'link_grup_beasiswa' => 'nullable|url|max:2048',
            'whatsapp_number' => 'nullable|string|max:20',
        ]);

        $mediaSosial = MediaSosial::findOrFail($id);
        $mediaSosial->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Media sosial berhasil diperbarui',
            'data' => $mediaSosial
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $mediaSosial = MediaSosial::findOrFail($id);
        $mediaSosial->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Media sosial berhasil dihapus'
        ]);
    }
      /**
     * Get the latest media sosial links.
     */
    public function getLatest(): JsonResponse
    {
        $mediaSosial = MediaSosial::latest()->first();
        
        if (!$mediaSosial) {
            // Return empty object with null values
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => null,
                    'twibbon_link' => null,
                    'instagram_link' => null,
                    'created_at' => null,
                    'updated_at' => null
                ]
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $mediaSosial
        ]);
    }
}
