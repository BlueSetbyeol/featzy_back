<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuItemOptionGroupRequest;
use App\Http\Requests\Menu\UpdateMenuItemOptionGroupRequest;
use App\Http\Resources\MenuItemOptionGroupResource;
use App\Models\MenuItem;
use App\Models\MenuItemOptionGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MenuItemOptionGroupController extends Controller
{
    public function store(StoreMenuItemOptionGroupRequest $request, MenuItem $menuItem): JsonResponse
    {
        $group = $menuItem->optionGroups()->create($request->validated());

        return MenuItemOptionGroupResource::make($group->refresh()->load('options'))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateMenuItemOptionGroupRequest $request, MenuItemOptionGroup $optionGroup): MenuItemOptionGroupResource
    {
        $optionGroup->update($request->validated());

        return MenuItemOptionGroupResource::make($optionGroup->load('options'));
    }

    public function destroy(MenuItemOptionGroup $optionGroup): Response
    {
        $optionGroup->delete();

        return response()->noContent();
    }
}
