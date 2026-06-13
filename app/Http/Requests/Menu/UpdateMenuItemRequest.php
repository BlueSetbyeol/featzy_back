<?php

namespace App\Http\Requests\Menu;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
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
        /** @var MenuItem $menuItem */
        $menuItem = $this->route('menuItem');

        return [
            'menu_category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('menu_categories', 'id')->where('restaurant_id', $menuItem->restaurant_id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'stock_quantity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'preparation_minutes' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
