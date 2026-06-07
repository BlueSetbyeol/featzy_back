<?php

namespace App\Http\Requests\Menu;

use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
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
        /** @var Restaurant $restaurant */
        $restaurant = $this->route('restaurant');

        return [
            'menu_category_id' => [
                'required',
                'integer',
                Rule::exists('menu_categories', 'id')->where('restaurant_id', $restaurant->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'position' => ['integer', 'min:0', 'max:65535'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'preparation_minutes' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
