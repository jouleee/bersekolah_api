<?php

namespace App\Http\Controllers;

use App\Models\KontenBersekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KontenBersekolahController extends Controller
{
    /**
     * Display a listing of the resource for public display (only published).
     */
    public function index(Request $request)
    {
        $query = KontenBersekolah::query();
        // For public endpoint, only show published content
        if (!$request->is('api/admin*')) {
            $query->where('status', 'published');
        }
        // Add search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('judul_halaman', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        // Add category filter
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        $perPage = $request->get('per_page', 6);
        $konten = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $konten->getCollection()->transform(function($item) {
            $item->gambar = $item->gambar_url; // Use the accessor
            return $item;
        });
        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil diambil',
            'data' => $konten->items(),
            'meta' => [
                'current_page' => $konten->currentPage(),
                'last_page' => $konten->lastPage(),
                'per_page' => $konten->perPage(),
                'total' => $konten->total(),
            ]
        ]);
    }

    /**
     * Display the specified resource for public display.
     */
    public function show($id)
    {
        $konten = KontenBersekolah::where(function($query) use ($id) {
                $query->where('id', $id)
                      ->orWhere('slug', $id);
            })
            ->where('status', 'published')
            ->firstOrFail();
        // Tambahkan path gambar pada response (tanpa mengubah database)
        $konten->gambar = $konten->gambar_url; // Use the accessor
        return response()->json([
            'success' => true,
            'message' => 'Konten berhasil diambil',
            'data' => $konten
        ]);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'judul_halaman' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:konten_bersekolah',
            'deskripsi' => 'required|string',
            'category' => 'required|string|max:100',
            'status' => 'required|in:draft,published,archived',
            'gambar' => 'nullable|image|max:2048', // 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            // Handle image upload if provided
            if ($request->hasFile('gambar')) {
                $image = $request->file('gambar');
                // Gunakan slug sebagai nama file dasar
                $slug = Str::slug($request->slug ?? $request->judul_halaman);
                $filename = $slug . '.' . $image->getClientOriginalExtension();
                // Simpan ke storage/app/public/admin/artikel
                $path = $image->storeAs('admin/artikel', $filename, 'public');
                $data['gambar'] = $filename; // hanya nama file
            } else {
                unset($data['gambar']);
            }
            $data['user_id'] = Auth::id() ?? 1;
            $konten = KontenBersekolah::create($data);
            // Return gambar url
            $konten->gambar = $konten->gambar_url; // Use the accessor
            return response()->json([
                'success' => true,
                'message' => 'Konten berhasil dibuat',
                'data' => $konten
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat konten',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Find the konten
        $konten = KontenBersekolah::findOrFail($id);
        
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'judul_halaman' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:konten_bersekolah,slug,' . $id,
            'deskripsi' => 'required|string',
            'category' => 'required|string|max:100',
            'status' => 'required|in:draft,published,archived',
            'gambar' => 'nullable|image|max:2048', // 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            // Handle image upload if provided
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama jika ada
                if ($konten->gambar) {
                    $oldPath = storage_path('app/public/admin/artikel/' . $konten->gambar);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $image = $request->file('gambar');
                $slug = Str::slug($request->slug ?? $request->judul_halaman);
                $filename = $slug . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('admin/artikel', $filename, 'public');
                $data['gambar'] = $filename; // hanya nama file
            } else {
                unset($data['gambar']); // jangan update kolom gambar jika tidak upload baru
            }
            $konten->update($data);
            $konten->gambar = $konten->gambar_url; // Use the accessor
            return response()->json([
                'success' => true,
                'message' => 'Konten berhasil diperbarui',
                'data' => $konten
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui konten',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, $id)
    {
        // Find the konten
        $konten = KontenBersekolah::findOrFail($id);
        
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $konten->update(['status' => $request->status]);
            return response()->json([
                'success' => true,
                'message' => 'Status konten berhasil diperbarui',
                'data' => $konten
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status konten',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $konten = KontenBersekolah::findOrFail($id);
            // Delete associated image if exists (from artikel folder)
            if ($konten->gambar) {
                $imagePath = storage_path('app/public/artikel/' . $konten->gambar);
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            $konten->delete();
            return response()->json([
                'success' => true,
                'message' => 'Konten berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus konten',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all konten/artikel (no pagination, for admin use)
     */
    public function all(Request $request)
    {
        $query = KontenBersekolah::query();

        // Hanya tampilkan yang "published" jika bukan endpoint admin
        if (!$request->is('api/admin*')) {
            $query->where('status', 'published');
        }

        // Fitur pencarian
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('judul_halaman', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter kategori
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Ambil semua data tanpa pagination
        $konten = $query->orderBy('created_at', 'desc')->get();

        // Modifikasi gambar menjadi URL lengkap
        $konten->transform(function($item) {
            $item->gambar = $item->gambar_url; // Use the accessor
            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Semua konten berhasil diambil',
            'data' => $konten
        ]);
    }

}
