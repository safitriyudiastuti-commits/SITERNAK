<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $type = $this->input('type', 'livestock');
        $categoryType = in_array($type, ['livestock', 'product'], true) ? $type : 'livestock';

        return [
            'kind' => ['sometimes', Rule::in(['category', 'breed', 'contest'])],
            'type' => ['required', Rule::in(['livestock', 'product'])],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'livestock_group' => ['nullable', Rule::in(['unggas', 'ternak_kecil', 'ternak_besar'])],
            'sale_modes' => ['required_if:kind,breed', 'array'],
            'sale_modes.*' => [Rule::in(['individual', 'batch'])],
            'purposes' => ['nullable', 'array'],
            'purposes.*' => [Rule::in(['bibit', 'pembesaran', 'siap_potong', 'indukan', 'kontes'])],
            'dynamic_fields' => ['nullable', 'array'],
            'dynamic_fields.*' => [Rule::in(['code', 'gender', 'age_months', 'weight_kg', 'contest_category'])],
        ];
    }
}
