<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\ReservationParticipant;
use App\Models\User;

class OrderPolicy
{
    /**
     * Any participant of the order's reservation, or the restaurant owner, may
     * view the order.
     */
    public function view(User $user, Order $order): bool
    {
        if ($order->restaurant()->where('owner_id', $user->id)->exists()) {
            return true;
        }

        return $this->isParticipant($user, $order);
    }

    /**
     * Any participant of the reservation may add their own items to the order.
     */
    public function addItem(User $user, Order $order): bool
    {
        return $this->isParticipant($user, $order);
    }

    /**
     * Only the organizer finalizes (places) the group order.
     */
    public function place(User $user, Order $order): bool
    {
        return $order->reservation()->where('organizer_id', $user->id)->exists();
    }

    /**
     * Kitchen lifecycle actions (prepare / serve / cancel) are owner-only.
     */
    public function manage(User $user, Order $order): bool
    {
        return $order->restaurant()->where('owner_id', $user->id)->exists();
    }

    private function isParticipant(User $user, Order $order): bool
    {
        return ReservationParticipant::query()
            ->where('reservation_id', $order->reservation_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
