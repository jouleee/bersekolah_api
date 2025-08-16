<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeasiswaApplicationRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'beasiswa_period_id' => 'required|exists:beasiswa_periods,id',
            'status' => 'required|in:pending,lolos_berkas,lolos_wawancara,diterima,ditolak',
            'submitted_at' => 'nullable|date',
        ];
    }
}
