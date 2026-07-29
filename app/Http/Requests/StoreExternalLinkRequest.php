<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Telah ditangani middleware CheckProjectRole
        return true;
    }

    public function rules(): array
    {
        return [
            'url'       => ['required', 'url', 'max:2083'],
            'label'     => ['nullable', 'string', 'max:100']
        ];
    }

    public function messages(): array
    {
        return [
            'url.required'      => 'URL tautan eksternal wajib diisi.',
            'url.url'           => 'Format URL tidak valid.',
            'url.max'           => 'URL tidak boleh lebih dari 2083 karakter.',
            'label.max'         => 'Label tidak boleh dari 100 karakter.'
        ];
    }

}