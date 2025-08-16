<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdditionalUploadRequest extends FormRequest
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
            'calon_beswan_id' => 'required|exists:calon_beswans,id',
            'upload_type_id' => 'required|exists:upload_types,id',
            'file_path' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10048',
            'keterangan' => 'nullable|string|max:255',
        ];
    }
}
