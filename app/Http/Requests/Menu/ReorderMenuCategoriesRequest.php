<?php

namespace App\Http\Requests\Menu;

use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderMenuCategoriesRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('menu_categories', 'id')->where('restaurant_id', $restaurant->id),
            ],
        ];
    }
}
