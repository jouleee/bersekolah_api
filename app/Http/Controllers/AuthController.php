<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Beswan;
use App\Models\KeluargaBeswan;
use App\Models\AlamatBeswan;
use App\Models\SekolahBeswan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Debug: Log request data
        Log::info('Register request data:', $request->all());
        
        try {
            $validatedData = $request->validate([
                'name' => 'required|max:255',
                'email' => 'required|email|unique:users',
                'phone' => 'required|numeric|unique:users',
                'password' => 'required|confirmed',
            ]);

            // Debug: Log validated data
            Log::info('Validated data:', $validatedData);

            // Gunakan database transaction untuk memastikan konsistensi data
            DB::beginTransaction();

            try {
                // Buat user baru
                $validatedData['password'] = Hash::make($validatedData['password']);
                $user = User::create($validatedData);

                // Buat record beswan yang terkait dengan user
                $beswan = Beswan::create([
                    'user_id' => $user->id,
                ]);

                // Simpan beswan_id untuk digunakan ke tabel lain
                $beswanId = $beswan->id;
                
                // Buat token untuk user secara manual
                $plainTextToken = bin2hex(random_bytes(20));
                
                $tokenId = DB::table('personal_access_tokens')->insertGetId([
                    'tokenable_type' => get_class($user),
                    'tokenable_id' => $user->id,
                    'name' => $request->email,
                    'token' => hash('sha256', $plainTextToken),
                    'abilities' => json_encode(['*']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $token = $tokenId . '|' . $plainTextToken;

                DB::commit();

                // Load relasi beswan untuk response secara manual
                $beswan = DB::table('beswan')->where('user_id', $user->id)->first();
                if ($beswan) {
                    $user->beswan_data = $beswan;
                }

                return response()->json([
                    'message' => 'Registrasi berhasil.',
                    'user' => $user,
                    'token' => $token,
                    'beswan_id' => $beswanId
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Database transaction failed:', ['error' => $e->getMessage()]);
                return response()->json([
                    'message' => 'Terjadi kesalahan saat menyimpan data.',
                    'errors' => ['server' => [$e->getMessage()]]
                ], 500);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', $e->errors());
            return response()->json([
                'message' => 'Data yang diberikan tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'errors' => ['server' => ['Gagal membuat akun']]
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Email atau kata sandi salah.',
                ], 401);
            }

            $user = Auth::user();
            
            // Hapus token yang sudah ada secara manual
            DB::table('personal_access_tokens')
                ->where('tokenable_type', get_class($user))
                ->where('tokenable_id', $user->id)
                ->delete();
            
            // Buat token baru secara manual
            $plainTextToken = bin2hex(random_bytes(20));
            
            $tokenId = DB::table('personal_access_tokens')->insertGetId([
                'tokenable_type' => get_class($user),
                'tokenable_id' => $user->id,
                'name' => 'auth_token',
                'token' => hash('sha256', $plainTextToken),
                'abilities' => json_encode([$user->role ?? 'user']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $token = $tokenId . '|' . $plainTextToken;
            
            // Load relasi beswan secara manual
            $beswan = DB::table('beswan')->where('user_id', $user->id)->first();
            if ($beswan) {
                $user->beswan_data = $beswan;
            }
            
            return response()->json([
                'message' => 'Login berhasil.',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat login.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Hapus token saat ini secara manual
            $accessToken = $request->bearerToken();
            if ($accessToken) {
                $tokenId = explode('|', $accessToken)[0] ?? null;
                if ($tokenId) {
                    DB::table('personal_access_tokens')
                        ->where('id', $tokenId)
                        ->delete();
                }
            }
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if ($user) {
                // Load relasi beswan secara manual
                $beswan = DB::table('beswan')->where('user_id', $user->id)->first();
                if ($beswan) {
                    $user->beswan_data = $beswan;
                }
            }
            return response()->json($user);
        } catch (\Exception $e) {
            Log::error('Error in me(): ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users (for admin/superadmin)
     */
    public function getUsers(Request $request)
    {
        // Optional: Filter by role if provided
        $query = User::query();
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->get();
        return response()->json($users);
    }

    /**
     * Create a new user (admin/superadmin only)
     */
    public function createUser(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'sometimes|numeric|unique:users',
            'password' => 'required|confirmed',
            'role' => 'required|in:admin,superadmin',
        ]);

        // Hash the password
        $validatedData['password'] = Hash::make($validatedData['password']);
        
        // Create the user
        $user = User::create($validatedData);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Update a user (admin/superadmin only)
     */
    public function updateUser(Request $request, $id)
    {
        // Find the user
        $user = User::findOrFail($id);

        // Validate the request
        $rules = [
            'name' => 'sometimes|required|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'phone' => 'sometimes|numeric|unique:users,phone,' . $id,
            'role' => 'sometimes|required|in:admin,superadmin',
        ];

        // Add password validation if provided
        if ($request->filled('password')) {
            $rules['password'] = 'required|confirmed';
        }

        $validatedData = $request->validate($rules);

        // Hash the password if provided
        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        // Update the user
        $user->update($validatedData);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete a user (admin/superadmin only)
     */
    public function deleteUser($id)
    {
        // Find the user
        $user = User::findOrFail($id);
        
        // Delete the user
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Update current user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // Validate the request
            $validatedData = $request->validate([
                'name' => 'required|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|nullable|string',
            ]);

            // Update the user
            $user->update($validatedData);

            return response()->json([
                'message' => 'Profile berhasil diperbarui',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
