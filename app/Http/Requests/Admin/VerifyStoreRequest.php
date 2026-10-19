<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyStoreRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'admin'; }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000'],
            'check_ktp' => ['required_if:decision,approved', 'accepted'],
            'check_location' => ['required_if:decision,approved', 'accepted'],
            'check_bank_document' => ['required_if:decision,approved', 'accepted'],
        ];
    }
}
