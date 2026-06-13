<?php

namespace App\Http\Controllers\Restaurant;

use App\Actions\Order\TransitionOrderStatusAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RestaurantOrderController extends Controller
{
    /**
     * Owner board of the restaurant's orders, filterable by status.
     */
    public function index(Request $request, Restaurant $restaurant): AnonymousResourceCollection
    {
        $orders = QueryBuilder::for($restaurant->orders())
            ->allowedFilters(AllowedFilter::exact('status'))
            ->allowedSorts('placed_at', 'created_at')
            ->defaultSort('-created_at')
            ->with('items.options')
            ->paginate()
            ->appends($request->query());

        return OrderResource::collection($orders);
    }

    public function prepare(Order $order, TransitionOrderStatusAction $action): OrderResource
    {
        return OrderResource::make($action->handle($order, OrderStatus::Preparing));
    }

    public function serve(Order $order, TransitionOrderStatusAction $action): OrderResource
    {
        return OrderResource::make($action->handle($order, OrderStatus::Served));
    }

    public function cancel(Order $order, TransitionOrderStatusAction $action): OrderResource
    {
        return OrderResource::make($action->handle($order, OrderStatus::Cancelled));
    }
}
