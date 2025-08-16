<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeasiswaPeriodsRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tahun' => 'required|integer|min:2020|max:2030',
            'nama_periode' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:1000',
            'mulai_pendaftaran' => 'required|date',
            'akhir_pendaftaran' => 'required|date|after:mulai_pendaftaran',
            'mulai_beasiswa' => 'required|date',
            'akhir_beasiswa' => 'required|date|after:mulai_beasiswa',
            'status' => 'required|in:draft,active,closed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun wajib diisi',
            'nama_periode.required' => 'Nama periode wajib diisi',
            'mulai_pendaftaran.required' => 'Tanggal mulai pendaftaran wajib diisi',
            'akhir_pendaftaran.after' => 'Tanggal akhir pendaftaran harus setelah tanggal mulai',
            'mulai_beasiswa.required' => 'Tanggal mulai beasiswa wajib diisi',
            'akhir_beasiswa.after' => 'Tanggal akhir beasiswa harus setelah tanggal mulai',
            'status.required' => 'Status wajib dipilih',
        ];
    }
}
