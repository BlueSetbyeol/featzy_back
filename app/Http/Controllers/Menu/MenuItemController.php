<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuItemRequest;
use App\Http\Requests\Menu\SyncMenuItemAllergensRequest;
use App\Http\Requests\Menu\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MenuItemController extends Controller
{
    private const WITH = ['category', 'allergens', 'optionGroups.options', 'media'];

    public function index(Restaurant $restaurant): AnonymousResourceCollection
    {
        $items = $restaurant->menuItems()
            ->with(['category', 'allergens', 'media'])
            ->orderBy('position')
            ->paginate();

        return MenuItemResource::collection($items);
    }

    public function store(StoreMenuItemRequest $request, Restaurant $restaurant): JsonResponse
    {
        $item = $restaurant->menuItems()->create($request->validated());

        return MenuItemResource::make($item->refresh()->load(self::WITH))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(MenuItem $menuItem): MenuItemResource
    {
        return MenuItemResource::make($menuItem->load(self::WITH));
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): MenuItemResource
    {
        $menuItem->update($request->validated());

        return MenuItemResource::make($menuItem->load(self::WITH));
    }

    public function destroy(MenuItem $menuItem): Response
    {
        $menuItem->delete();

        return response()->noContent();
    }

    public function syncAllergens(SyncMenuItemAllergensRequest $request, MenuItem $menuItem): MenuItemResource
    {
        $menuItem->allergens()->sync($request->validated('allergen_ids'));

        return MenuItemResource::make($menuItem->load(self::WITH));
    }
}
