<?php

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
    Storage::fake('public');
});

function ownedItem(): MenuItem
{
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $category = MenuCategory::factory()->for($restaurant)->create();

    return MenuItem::factory()->for($category, 'category')->create();
}

it('uploads a photo to a menu item', function () {
    $item = ownedItem();

    $this->postJson("/api/menu-items/{$item->id}/photos", [
        'file' => UploadedFile::fake()->image('dish.jpg'),
    ])->assertOk();

    expect($item->fresh()->getMedia('photos'))->toHaveCount(1);
});

it('rejects a non-image file', function () {
    $item = ownedItem();

    $this->postJson("/api/menu-items/{$item->id}/photos", [
        'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('deletes an owned photo', function () {
    $item = ownedItem();
    $item->addMedia(UploadedFile::fake()->image('dish.jpg'))->toMediaCollection('photos');
    $media = $item->getFirstMedia('photos');

    $this->deleteJson("/api/menu-items/{$item->id}/photos/{$media->id}")->assertNoContent();

    expect($item->fresh()->getMedia('photos'))->toHaveCount(0);
});

it('forbids uploading to a menu item of another owner', function () {
    actingAsRestaurateur();
    $foreign = MenuItem::factory()->create();

    $this->postJson("/api/menu-items/{$foreign->id}/photos", [
        'file' => UploadedFile::fake()->image('dish.jpg'),
    ])->assertForbidden();
});
