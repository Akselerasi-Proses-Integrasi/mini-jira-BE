<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class JoinProjectByCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'kode_proyek' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_proyek.required'      => 'Kode proyek wajib diisi.',
            'kode_proyek.string'        => 'Kode proyek harus berupa teks.',
            'kode_proyek.max'           => 'Kode proyek maksimal 20 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message'=> 'Validasi gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}