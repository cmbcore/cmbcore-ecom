<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;

class ReportsService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'revenue_by_day' => $this->revenueByDay(),
            'orders_by_status' => $this->ordersByStatus(),
            'top_products' => $this->topProducts(),
            'top_customers' => $this->topCustomers(),
            'conversion' => [
                'views_to_orders' => $this->viewsToOrdersRate(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function revenueByDay(): array
    {
        return Order::query()
            ->selectRaw('date(created_at) as report_date, sum(grand_total) as revenue, count(*) as orders_count')
            ->groupByRaw('date(created_at)')
            ->orderByDesc('report_date')
            ->limit(30)
            ->get()
            ->map(fn ($row): array => [
                'date' => (string) $row->report_date,
                'revenue' => (float) $row->revenue,
                'orders_count' => (int) $row->orders_count,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ordersByStatus(): array
    {
        return Order::query()
            ->selectRaw('order_status, count(*) as total')
            ->groupBy('order_status')
            ->get()
            ->map(fn ($row): array => [
                'status' => (string) $row->order_status,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(): array
    {
        return DB::table('order_items')
            ->selectRaw('product_id, product_name, sum(quantity) as sold_quantity, sum(line_total) as revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('sold_quantity')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'product_id' => $row->product_id,
                'product_name' => (string) $row->product_name,
                'sold_quantity' => (int) $row->sold_quantity,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topCustomers(): array
    {
        return DB::table('orders')
            ->selectRaw('coalesce(users.name, orders.customer_name) as customer_name, coalesce(users.email, orders.guest_email) as customer_email, count(*) as orders_count, sum(grand_total) as total_spent')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->groupBy('customer_name', 'customer_email')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'customer_name' => (string) $row->customer_name,
                'customer_email' => (string) $row->customer_email,
                'orders_count' => (int) $row->orders_count,
                'total_spent' => (float) $row->total_spent,
            ])
            ->all();
    }

    private function viewsToOrdersRate(): float
    {
        $productViews = (float) DB::table('products')->sum('view_count');
        $orders = (float) DB::table('orders')->count();

        if ($productViews <= 0) {
            return 0.0;
        }

        return round(($orders / $productViews) * 100, 2);
    }
}
