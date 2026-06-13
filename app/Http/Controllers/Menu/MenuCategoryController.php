<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\ReorderMenuCategoriesRequest;
use App\Http\Requests\Menu\StoreMenuCategoryRequest;
use App\Http\Requests\Menu\UpdateMenuCategoryRequest;
use App\Http\Resources\MenuCategoryResource;
use App\Models\MenuCategory;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MenuCategoryController extends Controller
{
    public function index(Restaurant $restaurant): AnonymousResourceCollection
    {
        return MenuCategoryResource::collection(
            $restaurant->menuCategories()->orderBy('position')->get(),
        );
    }

    public function store(StoreMenuCategoryRequest $request, Restaurant $restaurant): JsonResponse
    {
        $category = $restaurant->menuCategories()->create($request->validated());

        return MenuCategoryResource::make($category->refresh())
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateMenuCategoryRequest $request, MenuCategory $menuCategory): MenuCategoryResource
    {
        $menuCategory->update($request->validated());

        return MenuCategoryResource::make($menuCategory);
    }

    public function destroy(MenuCategory $menuCategory): Response
    {
        $menuCategory->delete();

        return response()->noContent();
    }

    public function reorder(ReorderMenuCategoriesRequest $request, Restaurant $restaurant): AnonymousResourceCollection
    {
        DB::transaction(function () use ($request, $restaurant): void {
            foreach ($request->validated('ids') as $position => $id) {
                $restaurant->menuCategories()->whereKey($id)->update(['position' => $position]);
            }
        });

        return MenuCategoryResource::collection(
            $restaurant->menuCategories()->orderBy('position')->get(),
        );
    }
}
