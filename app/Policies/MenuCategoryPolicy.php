<?php

namespace App\Policies;

use App\Models\MenuCategory;
use App\Models\User;

class MenuCategoryPolicy
{
    public function update(User $user, MenuCategory $menuCategory): bool
    {
        return $menuCategory->restaurant?->owner_id === $user->id;
    }

    public function delete(User $user, MenuCategory $menuCategory): bool
    {
        return $menuCategory->restaurant?->owner_id === $user->id;
    }
}
