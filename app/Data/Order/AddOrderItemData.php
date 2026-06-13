<?php

namespace App\Data\Order;

use Spatie\LaravelData\Data;

class AddOrderItemData extends Data
{
    /**
     * @param  array<int, array{id: int, quantity?: int}>  $options
     */
    public function __construct(
        public int $menu_item_id,
        public int $reservation_participant_id,
        public int $quantity,
        public array $options = [],
        public ?string $notes = null,
    ) {}
}
