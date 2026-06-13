<?php

namespace App\Policies;

use App\Models\MenuItemOption;
use App\Models\User;

class MenuItemOptionPolicy
{
    public function update(User $user, MenuItemOption $menuItemOption): bool
    {
        return $menuItemOption->group?->menuItem?->restaurant?->owner_id === $user->id;
    }

    public function delete(User $user, MenuItemOption $menuItemOption): bool
    {
        return $menuItemOption->group?->menuItem?->restaurant?->owner_id === $user->id;
    }
}
