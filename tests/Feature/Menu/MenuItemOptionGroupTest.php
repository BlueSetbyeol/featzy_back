<?php

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemOptionGroup;
use App\Models\Restaurant;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

function ownedMenuItemForGroups(): MenuItem
{
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $category = MenuCategory::factory()->for($restaurant)->create();

    return MenuItem::factory()->for($category, 'category')->create();
}

it('creates an option group', function () {
    $item = ownedMenuItemForGroups();

    $this->postJson("/api/menu-items/{$item->id}/option-groups", [
        'name' => 'Cuisson',
        'min_select' => 1,
        'max_select' => 1,
        'is_required' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Cuisson')
        ->assertJsonPath('data.min_select', 1)
        ->assertJsonPath('data.max_select', 1)
        ->assertJsonPath('data.menu_item_id', $item->id);
});

it('creates an option group with defaults', function () {
    $item = ownedMenuItemForGroups();

    $this->postJson("/api/menu-items/{$item->id}/option-groups", ['name' => 'Suppléments'])
        ->assertCreated()
        ->assertJsonPath('data.min_select', 0)
        ->assertJsonPath('data.is_required', false);
});

it('rejects max_select lower than min_select on create', function () {
    $item = ownedMenuItemForGroups();

    $this->postJson("/api/menu-items/{$item->id}/option-groups", [
        'name' => 'X',
        'min_select' => 3,
        'max_select' => 1,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('max_select');
});

it('rejects an update that makes min exceed the existing max', function () {
    $item = ownedMenuItemForGroups();
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create([
        'min_select' => 0,
        'max_select' => 2,
    ]);

    $this->patchJson("/api/menu-item-option-groups/{$group->id}", ['min_select' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors('max_select');
});

it('updates an option group', function () {
    $item = ownedMenuItemForGroups();
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create(['name' => 'Old']);

    $this->patchJson("/api/menu-item-option-groups/{$group->id}", ['name' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New');
});

it('deletes an option group', function () {
    $item = ownedMenuItemForGroups();
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create();

    $this->deleteJson("/api/menu-item-option-groups/{$group->id}")->assertNoContent();

    $this->assertDatabaseMissing('menu_item_option_groups', ['id' => $group->id]);
});

it('forbids managing an option group of another owner', function () {
    actingAsRestaurateur();
    $foreign = MenuItemOptionGroup::factory()->create();

    $this->patchJson("/api/menu-item-option-groups/{$foreign->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

it('denies (does not error) when the parent menu item was soft-deleted', function () {
    $item = ownedMenuItemForGroups();
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create();
    $item->delete();

    $this->patchJson("/api/menu-item-option-groups/{$group->id}", ['name' => 'X'])
        ->assertForbidden();
});
