<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBeasiswaPeriodsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tahun' => 'sometimes|integer|min:2020|max:2030',
            'nama_periode' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
            'mulai_pendaftaran' => 'sometimes|date',
            'akhir_pendaftaran' => 'sometimes|date|after:mulai_pendaftaran',
            'mulai_beasiswa' => 'sometimes|date',
            'akhir_beasiswa' => 'sometimes|date|after:mulai_beasiswa',
            'status' => 'sometimes|in:draft,active,closed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'tahun.integer' => 'Tahun harus berupa angka',
            'tahun.min' => 'Tahun minimal 2020',
            'tahun.max' => 'Tahun maksimal 2030',
            'nama_periode.max' => 'Nama periode maksimal 255 karakter',
            'mulai_pendaftaran.date' => 'Format tanggal mulai pendaftaran tidak valid',
            'akhir_pendaftaran.date' => 'Format tanggal akhir pendaftaran tidak valid',
            'akhir_pendaftaran.after' => 'Tanggal akhir pendaftaran harus setelah tanggal mulai pendaftaran',
            'akhir_beasiswa.after' => 'Tanggal akhir beasiswa harus setelah tanggal mulai beasiswa',
            'status.in' => 'Status harus salah satu: draft, active, closed',
        ];
    }
}
