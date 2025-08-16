<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function showForm()
    {
        return view('form');
    }

    public function sendEmail(Request $request)
    {
        // Validasi form
        $validated = $request->validate([
            'nama' => 'required|string',
            'email' => 'required|email',
            'pesan' => 'required|string',
        ]);

        // Kirim email
        Mail::to('izzuddinazzam@upi.edu')->send(new ContactFormMail($validated));

        return back()->with('success', 'Pesan berhasil dikirim ke email!');
    }

    /**
     * API endpoint for sending contact form email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function kirimPesan(Request $request)
    {
        try {
            // Validate request data
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'pesan' => 'required|string',
            ]);

            // Log incoming request (optional, good for debugging)
            Log::info('Contact form submission received', [
                'name' => $validated['nama'],
                'email' => $validated['email']
            ]);

            // Send email
            Mail::to('izzuddinazzam@upi.edu')->send(new ContactFormMail($validated));

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim!'
            ], 200);
        } catch (\Exception $e) {
            // Log error
            Log::error('Contact form email sending failed: ' . $e->getMessage());

            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan. Silakan coba lagi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
