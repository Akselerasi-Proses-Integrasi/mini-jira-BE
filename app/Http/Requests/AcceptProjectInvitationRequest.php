<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\ProjectInvitation;

class AcceptProjectInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [];
    }

    // Resolve invitation berdasakan token.
    public function prepareForValidation(): void
    {
        $token = $this->route('token');
        $this->merge([
            'invitation'    => ProjectInvitation::valid()->where('token', $token)->first()
        ]);
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->invitation) {
                $validator->errors()->add('token', 'Undangan tidak valid, sudah kadaluarsa, atau sudah diterima.');
            }

            if ($this->invitation && strtolower($this->invitation->email) !== strtolower(auth()->user()->email)) {
                $validator->errors()->add('email', 'Email kamu tidak cocok dengan yang diundang.');
            }
        });
    }
    

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Tidak dapat menerima undangan.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}

