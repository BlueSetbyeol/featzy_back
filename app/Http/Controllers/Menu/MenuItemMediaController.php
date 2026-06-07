<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\UploadMenuItemPhotoRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MenuItemMediaController extends Controller
{
    public function store(UploadMenuItemPhotoRequest $request, MenuItem $menuItem): MenuItemResource
    {
        $menuItem->addMediaFromRequest('file')->toMediaCollection('photos');

        return MenuItemResource::make($menuItem->load(['category', 'allergens', 'optionGroups.options', 'media']));
    }

    public function destroy(MenuItem $menuItem, Media $media): Response
    {
        abort_unless(
            $media->model_type === $menuItem->getMorphClass() && (int) $media->model_id === $menuItem->id,
            Response::HTTP_NOT_FOUND,
        );

        $media->delete();

        return response()->noContent();
    }
}
