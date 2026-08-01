<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApprovalModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('project')->owner_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'approval_mode' => 'required|in:default,restricted',
        ];
    }
}