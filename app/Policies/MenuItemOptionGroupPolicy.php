<?php

namespace App\Policies;

use App\Models\MenuItemOptionGroup;
use App\Models\User;

class MenuItemOptionGroupPolicy
{
    public function update(User $user, MenuItemOptionGroup $optionGroup): bool
    {
        return $optionGroup->menuItem?->restaurant?->owner_id === $user->id;
    }

    public function delete(User $user, MenuItemOptionGroup $optionGroup): bool
    {
        return $optionGroup->menuItem?->restaurant?->owner_id === $user->id;
    }
}
