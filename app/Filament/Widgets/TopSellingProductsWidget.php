<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopSellingProductsWidget extends Widget
{
    protected string $view = 'filament.widgets.top-selling-products';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.status', ['cancelled'])
            ->selectRaw('
                order_items.product_id,
                COALESCE(MAX(products.name), MAX(order_items.product_name)) as name,
                MAX(products.images) as images_json,
                SUM(order_items.quantity) as qty_sold,
                SUM(order_items.total_price) as revenue
            ')
            ->groupBy('order_items.product_id')
            ->orderByDesc('qty_sold')
            ->limit(10)
            ->get();

        return [
            'rows' => $rows,
        ];
    }
}