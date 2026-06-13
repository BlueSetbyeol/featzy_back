<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;

class OrderItemPolicy
{
    /**
     * A participant manages only the items they added. Whether the parent order
     * is still editable (pending) is a state guard handled in the controller, not
     * an authorization concern.
     */
    public function update(User $user, OrderItem $orderItem): bool
    {
        return $this->ownsItem($user, $orderItem);
    }

    public function delete(User $user, OrderItem $orderItem): bool
    {
        return $this->ownsItem($user, $orderItem);
    }

    private function ownsItem(User $user, OrderItem $orderItem): bool
    {
        return $orderItem->participant()->where('user_id', $user->id)->exists();
    }
}
