<?php

namespace App\Http\Resources;

use App\Models\MenuItemOptionGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MenuItemOptionGroup
 */
class MenuItemOptionGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'name' => $this->name,
            'min_select' => $this->min_select,
            'max_select' => $this->max_select,
            'is_required' => $this->is_required,
            'position' => $this->position,
            'options' => MenuItemOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
