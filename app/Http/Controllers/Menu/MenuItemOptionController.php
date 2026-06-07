<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuItemOptionRequest;
use App\Http\Requests\Menu\UpdateMenuItemOptionRequest;
use App\Http\Resources\MenuItemOptionResource;
use App\Models\MenuItemOption;
use App\Models\MenuItemOptionGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MenuItemOptionController extends Controller
{
    public function store(StoreMenuItemOptionRequest $request, MenuItemOptionGroup $optionGroup): JsonResponse
    {
        $option = $optionGroup->options()->create($request->validated());

        return MenuItemOptionResource::make($option->refresh())
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateMenuItemOptionRequest $request, MenuItemOption $menuItemOption): MenuItemOptionResource
    {
        $menuItemOption->update($request->validated());

        return MenuItemOptionResource::make($menuItemOption);
    }

    public function destroy(MenuItemOption $menuItemOption): Response
    {
        $menuItemOption->delete();

        return response()->noContent();
    }
}
