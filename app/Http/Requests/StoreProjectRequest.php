<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'nama_proyek'  => ['required', 'string', 'max:150'],
            'deskripsi'    => ['nullable', 'string'],
            'tgl_mulai'    => ['required', 'date'],
            'tgl_selesai'  => ['required', 'date', 'after:tgl_mulai'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_proyek.required' => 'Nama proyek harus diisi.',
            'nama_proyek.max'      => 'Nama proyek maksimal 150 karakter.',
            'tgl_mulai.required'   => 'Tanggal mulai harus diisi.',
            'tgl_selesai.required' => 'Tanggal selesai harus diisi.',
            'tgl_selesai.after'    => 'Tanggal selesai harus setelah tanggal mulai.',
        ];
    }
}