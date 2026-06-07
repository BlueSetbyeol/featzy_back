<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMenuItemOptionGroupRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'min_select' => ['integer', 'min:0', 'max:255'],
            'max_select' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_required' => ['boolean'],
            'position' => ['integer', 'min:0', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = (int) $this->input('min_select', 0);
            $max = $this->input('max_select');

            if ($max !== null && (int) $max < $min) {
                $validator->errors()->add('max_select', 'The max select must be greater than or equal to min select.');
            }
        });
    }
}
