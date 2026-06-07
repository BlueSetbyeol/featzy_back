<?php

namespace App\Http\Requests\Menu;

use App\Models\MenuItemOptionGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMenuItemOptionGroupRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'min_select' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'max_select' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var MenuItemOptionGroup $group */
            $group = $this->route('optionGroup');

            $min = $this->has('min_select') ? (int) $this->input('min_select') : $group->min_select;
            $max = $this->has('max_select') ? $this->input('max_select') : $group->max_select;

            if ($max !== null && (int) $max < $min) {
                $validator->errors()->add('max_select', 'The max select must be greater than or equal to min select.');
            }
        });
    }
}
