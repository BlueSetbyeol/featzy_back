<?php

use App\Models\MenuCategory;
use App\Models\Restaurant;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

it('lists categories ordered by position', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    MenuCategory::factory()->for($restaurant)->create(['name' => 'B', 'position' => 2]);
    MenuCategory::factory()->for($restaurant)->create(['name' => 'A', 'position' => 1]);

    $this->getJson("/api/restaurants/{$restaurant->id}/menu-categories")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'A');
});

it('creates a category with defaults', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();

    $this->postJson("/api/restaurants/{$restaurant->id}/menu-categories", ['name' => 'Desserts'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Desserts')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.position', 0);
});

it('updates a category', function () {
    $owner = actingAsRestaurateur();
    $category = MenuCategory::factory()
        ->for(Restaurant::factory()->for($owner, 'owner'))
        ->create(['name' => 'Old']);

    $this->patchJson("/api/menu-categories/{$category->id}", ['name' => 'New', 'is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.name', 'New')
        ->assertJsonPath('data.is_active', false);
});

it('soft-deletes a category', function () {
    $owner = actingAsRestaurateur();
    $category = MenuCategory::factory()->for(Restaurant::factory()->for($owner, 'owner'))->create();

    $this->deleteJson("/api/menu-categories/{$category->id}")->assertNoContent();

    $this->assertSoftDeleted($category);
});

it('reorders categories', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $a = MenuCategory::factory()->for($restaurant)->create(['position' => 0]);
    $b = MenuCategory::factory()->for($restaurant)->create(['position' => 1]);

    $this->patchJson("/api/restaurants/{$restaurant->id}/menu-categories/reorder", [
        'ids' => [$b->id, $a->id],
    ])
        ->assertOk()
        ->assertJsonPath('data.0.id', $b->id);

    expect($a->fresh()->position)->toBe(1);
    expect($b->fresh()->position)->toBe(0);
});

it('rejects reordering with a category from another restaurant', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $foreign = MenuCategory::factory()->create();

    $this->patchJson("/api/restaurants/{$restaurant->id}/menu-categories/reorder", [
        'ids' => [$foreign->id],
    ])->assertStatus(422);
});

it('forbids managing a category of another owner', function () {
    actingAsRestaurateur();
    $foreign = MenuCategory::factory()->create();

    $this->patchJson("/api/menu-categories/{$foreign->id}", ['name' => 'Hack'])->assertForbidden();
});

it('forbids creating a category in another owner restaurant', function () {
    actingAsRestaurateur();
    $foreign = Restaurant::factory()->create();

    $this->postJson("/api/restaurants/{$foreign->id}/menu-categories", ['name' => 'X'])
        ->assertForbidden();
});
