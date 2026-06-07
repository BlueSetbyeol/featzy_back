<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncMenuItemAllergensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'allergen_ids' => ['present', 'array'],
            'allergen_ids.*' => ['integer', 'distinct', Rule::exists('allergens', 'id')],
        ];
    }
}
