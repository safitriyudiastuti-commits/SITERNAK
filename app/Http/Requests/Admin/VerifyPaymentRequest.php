<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'admin'; }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['paid', 'rejected'])],
            'verification_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000'],
        ];
    }
}
