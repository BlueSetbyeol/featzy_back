<?php

namespace App\Http\Resources;

use App\Models\OrderItemOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItemOption
 */
class OrderItemOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'menu_item_option_id' => $this->menu_item_option_id,
            'label_snapshot' => $this->label_snapshot,
            'price_delta_snapshot' => $this->price_delta_snapshot,
            'quantity' => $this->quantity,
        ];
    }
}
