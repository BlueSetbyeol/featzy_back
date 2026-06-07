<?php

use App\Models\Allergen;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

function ownedMenuItem(): MenuItem
{
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $category = MenuCategory::factory()->for($restaurant)->create();

    return MenuItem::factory()->for($category, 'category')->create();
}

it('syncs allergens onto a menu item', function () {
    $item = ownedMenuItem();
    $a = Allergen::factory()->create();
    $b = Allergen::factory()->create();

    $this->putJson("/api/menu-items/{$item->id}/allergens", ['allergen_ids' => [$a->id, $b->id]])
        ->assertOk()
        ->assertJsonCount(2, 'data.allergens');

    expect($item->allergens()->count())->toBe(2);
});

it('clears allergens with an empty array', function () {
    $item = ownedMenuItem();
    $item->allergens()->attach(Allergen::factory()->create());

    $this->putJson("/api/menu-items/{$item->id}/allergens", ['allergen_ids' => []])
        ->assertOk()
        ->assertJsonCount(0, 'data.allergens');

    expect($item->allergens()->count())->toBe(0);
});

it('rejects an unknown allergen', function () {
    $item = ownedMenuItem();

    $this->putJson("/api/menu-items/{$item->id}/allergens", ['allergen_ids' => [99999]])
        ->assertStatus(422)
        ->assertJsonValidationErrors('allergen_ids.0');
});
