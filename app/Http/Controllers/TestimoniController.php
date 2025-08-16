<?php

namespace App\Http\Controllers;

use App\Models\Testimoni;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TestimoniController extends Controller
{
    /**
     * Display a listing of the resource.
     * For public access: only active testimonials
     * For admin access: all testimonials
     */
    public function index(Request $request)
    {
        try {
            // Check if this is an admin request (has auth token)
            $user = Auth::user();
            
            if ($user && in_array($user->role, ['admin', 'superadmin'])) {
                // Admin can see all testimonials
                $testimoni = Testimoni::orderBy('tanggal_input', 'desc')->get();
            } else {
                // Public can only see active testimonials
                $testimoni = Testimoni::where('status', 'active')
                    ->orderBy('tanggal_input', 'desc')
                    ->get();
            }
            
            // Add foto_testimoni_url to each testimoni
            $testimoni->transform(function($item) {
                $item->foto_testimoni_url = $item->foto_testimoni_url;
                return $item;
            });
            
            return response()->json([
                'success' => true,
                'data' => $testimoni,
                'total' => $testimoni->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching testimonials: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch testimonials'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nama' => 'required|string|max:100',
                'angkatan_beswan' => 'required|string|max:20',
                'sekarang_dimana' => 'nullable|string|max:255',
                'isi_testimoni' => 'required|string',
                'foto_testimoni' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'status' => 'in:active,inactive'
            ]);

            if ($request->hasFile('foto_testimoni')) {
                $file = $request->file('foto_testimoni');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('admin/testimoni', $filename, 'public');
                $validatedData['foto_testimoni'] = $filename;
            }

            $validatedData['status'] = $validatedData['status'] ?? 'inactive';
            $validatedData['tanggal_input'] = now();

            $testimoni = Testimoni::create($validatedData);
            $testimoni->foto_testimoni_url = $testimoni->foto_testimoni_url;

            return response()->json([
                'success' => true,
                'message' => 'Testimoni created successfully.',
                'data' => $testimoni
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating testimoni: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create testimoni'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Testimoni $testimoni)
    {
        $testimoni->foto_testimoni_url = $testimoni->foto_testimoni_url;
        return response()->json([
            'success' => true,
            'data' => $testimoni
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $testimoni = Testimoni::findOrFail($id);
            $validatedData = $request->validate([
                'nama' => 'sometimes|required|string|max:100',
                'angkatan_beswan' => 'sometimes|required|string|max:20',
                'sekarang_dimana' => 'nullable|string|max:255',
                'isi_testimoni' => 'sometimes|required|string',
                'foto_testimoni' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'status' => 'in:active,inactive'
            ]);
            if ($request->hasFile('foto_testimoni')) {
                if ($testimoni->foto_testimoni) {
                    Storage::disk('public')->delete('admin/testimoni/' . $testimoni->foto_testimoni);
                }
                $file = $request->file('foto_testimoni');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('admin/testimoni', $filename, 'public');
                $validatedData['foto_testimoni'] = $filename;
            }
            $testimoni->update($validatedData);
            $testimoni->foto_testimoni_url = $testimoni->foto_testimoni_url;
            return response()->json([
                'success' => true,
                'message' => 'Testimoni updated successfully.',
                'data' => $testimoni->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating testimoni: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update testimoni'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $testimoni = Testimoni::findOrFail($id);
            if ($testimoni->foto_testimoni) {
                Storage::disk('public')->delete('testimoni/' . $testimoni->foto_testimoni);
            }
            $testimoni->delete();
            return response()->json([
                'success' => true,
                'message' => 'Testimoni deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting testimoni: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete testimoni'
            ], 500);
        }
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, Testimoni $testimoni)
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);

            $testimoni->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Testimoni status updated successfully.',
                'data' => $testimoni->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating testimoni status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update testimoni status'
            ], 500);
        }
    }
}
