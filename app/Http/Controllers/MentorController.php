<?php

namespace App\Http\Controllers;

use App\Models\Mentor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MentorController extends Controller
{
    // Get all mentors
    public function index()
    {
        $mentors = Mentor::all();
        $mentors->transform(function($mentor) {
            // Ensure photo_url is generated
            $mentor->photo_url = $mentor->photo_url;
            return $mentor;
        });
        return response()->json([
            'success' => true,
            'data' => $mentors,
            'total' => Mentor::count()
        ]);
    }

    // Get total mentors only
    public function total()
    {
        return response()->json([
            'total' => Mentor::count()
        ]);
    }

    public function store(Request $request)
    {
        try {
            // Log request data untuk debugging
            Log::info('Mentor store request:', [
                'all_data' => $request->all(),
                'has_file' => $request->hasFile('photo'),
                'files' => $request->allFiles()
            ]);

            $data = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:mentors,email',
                'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:10240', // max 10MB
            ]);

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('admin/mentor', 'public');
                $data['photo'] = basename($photoPath);
            }

            $mentor = Mentor::create($data);
            $mentor->load([]); // Refresh model to get accessors
            $mentor->photo_url = $mentor->photo_url; // Trigger accessor

            return response()->json([
                'success' => true,
                'data' => $mentor,
                'message' => 'Mentor berhasil ditambahkan'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Mentor validation error:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Mentor store error:', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan mentor: ' . $e->getMessage()
            ], 500);
        }
    }

    // Show mentor by id
    public function show($id)
    {
        $mentor = Mentor::findOrFail($id);
        $mentor->photo_url = $mentor->photo_url; // Trigger accessor
        return response()->json([
            'success' => true,
            'data' => $mentor
        ]);
    }

    // Update mentor
    public function update(Request $request, $id)
    {
        try {
            $mentor = Mentor::findOrFail($id);

            // Log request data untuk debugging
            Log::info('Mentor update request:', [
                'id' => $id,
                'all_data' => $request->all(),
                'has_file' => $request->hasFile('photo'),
                'files' => $request->allFiles()
            ]);

            $data = $request->validate([
                'name' => 'sometimes|string',
                'email' => 'sometimes|email|unique:mentors,email,' . $id,
                'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            ]);

            if ($request->hasFile('photo')) {
                // Hapus foto lama jika ada
                if ($mentor->photo) {
                    $oldPath = storage_path('app/public/admin/mentor/' . basename($mentor->photo));
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $photoPath = $request->file('photo')->store('admin/mentor', 'public');
                $data['photo'] = basename($photoPath);
            }

            $mentor->update($data);
            $mentor->refresh(); // Refresh to get updated accessors
            $mentor->photo_url = $mentor->photo_url; // Trigger accessor

            return response()->json([
                'success' => true,
                'data' => $mentor,
                'message' => 'Mentor berhasil diperbarui'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Mentor update validation error:', [
                'id' => $id,
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Mentor update error:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui mentor: ' . $e->getMessage()
            ], 500);
        }
    }


    // Delete mentor
    public function destroy($id)
    {
        $mentor = Mentor::findOrFail($id);
        $mentor->delete();
        return response()->json(['message' => 'Mentor deleted']);
    }
}
