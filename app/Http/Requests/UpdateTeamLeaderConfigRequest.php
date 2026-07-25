<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTeamLeaderConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'has_team_leader' => ['required', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'has_team_leader.required'  => 'Konfigurasi tim wajib diisi.',
            'has_team_leader.boolean'   => 'Nilai konfigurasi tim harus berupa true or false.'
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validasi gagal.',
            'errors'  => $validator->errors(),
        ], 422));
    }

}