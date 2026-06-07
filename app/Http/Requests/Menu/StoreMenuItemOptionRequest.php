<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemOptionRequest extends FormRequest
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
            'price_delta' => ['required', 'integer'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'position' => ['integer', 'min:0', 'max:65535'],
        ];
    }
}
