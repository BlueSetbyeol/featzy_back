<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Participation is enforced by the can:addItem,order middleware.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Order $order */
        $order = $this->route('order');

        return [
            'menu_item_id' => [
                'required',
                'integer',
                // Must be an available item of the order's own restaurant.
                Rule::exists('menu_items', 'id')
                    ->where('restaurant_id', $order->restaurant_id)
                    ->where('is_available', true)
                    ->whereNull('deleted_at'),
            ],
            'reservation_participant_id' => [
                'required',
                'integer',
                // A participant orders only for their own seat in this reservation.
                Rule::exists('reservation_participants', 'id')
                    ->where('reservation_id', $order->reservation_id)
                    ->where('user_id', $this->user()->id),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'options' => ['array'],
            'options.*.id' => ['required', 'integer', 'distinct', 'exists:menu_item_options,id'],
            'options.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
