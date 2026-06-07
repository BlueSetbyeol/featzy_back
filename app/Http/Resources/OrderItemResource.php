<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'reservation_participant_id' => $this->reservation_participant_id,
            'menu_item_id' => $this->menu_item_id,
            'name_snapshot' => $this->name_snapshot,
            'quantity' => $this->quantity,
            'unit_price_snapshot' => $this->unit_price_snapshot,
            'options_total_snapshot' => $this->options_total_snapshot,
            'line_total' => $this->line_total,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'options' => OrderItemOptionResource::collection($this->whenLoaded('options')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
