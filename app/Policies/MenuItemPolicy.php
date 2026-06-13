<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    public function view(User $user, MenuItem $menuItem): bool
    {
        return $menuItem->restaurant?->owner_id === $user->id;
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $menuItem->restaurant?->owner_id === $user->id;
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $menuItem->restaurant?->owner_id === $user->id;
    }
}
