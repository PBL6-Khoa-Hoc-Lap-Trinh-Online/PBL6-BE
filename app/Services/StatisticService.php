<?php

namespace App\Services;

use App\Models\Import;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Traits\APIResponse;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatisticService
{
    use APIResponse;
    public function getOverview(Request $request)
    {
        $year = $request->year ?? Carbon::now();
        $user = User::whereYear('user_created_at', $year)->count();
        $order = Order::whereYear('order_created_at', $year)->count();
        $product = Product::whereYear('product_created_at', $year)->count();
        //Doanh thu theo từng năm
        $yearRevenue = Order::where('order_status', 'delivered')->whereYear('order_created_at', $year)
            ->selectRaw('YEAR(order_created_at) as year, sum(order_total_amount) as total')
            ->groupBy('year')
            ->get();
        $supplier = Supplier::whereYear('supplier_created_at', $year)->count();
        $yearImport = Import::whereYear('import_created_at', $year)->selectRaw('YEAR(import_created_at) as year, sum(import_total_amount) as total')
            ->groupBy('year')->get();
        $data = [
            'user' => $user,
            'order' => $order,
            'product' => $product,
            'yearRevenue' => $yearRevenue,
            'supplier' => $supplier,
            'yearImport' => $yearImport
        ];
        return $this->responseSuccessWithData($data, "Thống kê tổng quan", 200);
    }
    public function getRevenue(Request $request)
    {
        try {

            if ($request->start_date) {
                $startDate = Carbon::parse($request->start_date);
            } else {
                $startDate = Order::orderBy('order_created_at', 'asc')->value('order_created_at');
            }

            if ($request->end_date) {
                $endDate = Carbon::parse($request->end_date);
            } else {
                $endDate = Carbon::now();
            }
            //Doanh thu theo từng ngày
            $dailyRevenue = Order::where('order_status', 'delivered')
                ->whereDate('order_created_at', '>=', $startDate)
                ->whereDate('order_created_at', '<=', $endDate)
                ->selectRaw('DATE(order_created_at) as date, sum(order_total_amount) as revenue_total')
                ->groupBy('date')
                ->get();
            $result = $dailyRevenue->pluck('revenue_total', 'date')->toArray();
            $dates = [];
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $dates[] = $date->toDateString();
            }
            foreach ($dates as $date) {
                if (array_key_exists($date, $result)) {
                    $result[$date] = $result[$date];
                } else {
                    $result[$date] = 0;
                }
            }
            ksort($result);
            return $this->responseSuccessWithData($result, 'Lấy doanh thu thành công!', 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function getOrders(Request $request)
    {
        try {
            //Tổng số đơn hàng
            $totalOrders = Order::count();
            //Số đơn hàng theo từng trạng thái
            $ordersByStatus = Order::selectRaw('order_status, count(order_id) as total')
                ->groupBy('order_status')
                ->get();
            $orderStatusPending = Order::where("order_status", "pending")->count();
            $orderStatusConfirmed = Order::where("order_status", "confirmed")->count();
            $orderStatusShipped = Order::where("order_status", "shipped")->count();
            $orderStatusDelivered = Order::where("order_status", "delivered")->count();
            $orderStatusCancelled = Order::where("order_status", "cancelled")->count();
            $data = [
                'total_orders' => $totalOrders,
                'order_pending' => $orderStatusPending,
                'order_confirmed' => $orderStatusConfirmed,
                'order_shipped' => $orderStatusShipped,
                'order_delivered' => $orderStatusDelivered,
                'order_cancelled' => $orderStatusCancelled
            ];
            return $this->responseSuccessWithData($data, 'Lấy số đơn hàng thành công!', 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function getProfit(Request $request)
    {
        try {
            if ($request->start_date) {
                $startDate = Carbon::parse($request->start_date);
            } else {
                $startDate = Import::orderBy('import_created_at', 'asc')->value('import_created_at');
            }

            if ($request->end_date) {
                $endDate = Carbon::parse($request->end_date);
            } else {
                $endDate = Carbon::now();
            }
            // // Profit by product
            $importProduct = Import::selectRaw('product_id, MAX(import_details.import_price) as product_price')
                ->join('import_details', 'imports.import_id', '=', 'import_details.import_id')
                ->groupBy('product_id')
                ->get();

            $orderProduct = Order::where('order_status', 'delivered')
                ->whereDate('order_created_at', '>=', $startDate)
                ->whereDate('order_created_at', '<=', $endDate)
                ->join('order_details', 'orders.order_id', '=', 'order_details.order_id')
                ->selectRaw('product_id,sum(order_quantity) as quantity, sum(order_total_price) as total')
                ->groupBy('product_id')
                ->get();

            // return $this->responseSuccessWithData($importProduct, 'Lấy lợi nhuận thành công!', 200);

            $profitByProduct = [];
            $temp = [];
            foreach ($orderProduct as $product) {
                $importTotal = $importProduct->where('product_id', $product->product_id)->first();
                $quantity = $product->quantity;

                $profit = $product->total - $importTotal->product_price * $quantity;
                $profitByProduct[] = [
                    'product_id' => $product->product_id,
                    'profit' => $profit,
                ];
            }

            // // Profit by month
            // $monthlyImport = Import::whereBetween('import_created_at', [$startDate, $endDate])
            //     ->selectRaw('YEAR(import_created_at) as year, MONTH(import_created_at) as month, sum(import_total_amount) as total')
            //     ->groupBy('year', 'month')
            //     ->get();
            // $monthlyOrder = Order::where('order_status', 'delivered')
            // ->where('payment_status', 'paid')
            // ->whereBetween('order_created_at', [$startDate, $endDate])
            //     ->selectRaw('YEAR(order_created_at) as year, MONTH(order_created_at) as month, sum(order_total_amount) as total')
            //     ->groupBy('year', 'month')
            //     ->get();

            // $monthlyProfit = [];
            // foreach ($monthlyOrder as $order) {
            //     $importTotal = $monthlyImport->where('year', $order->year)
            //     ->where('month', $order->month)
            //     ->sum('total') ?? 0;

            //     $profit = $order->total - $importTotal;
            //     $monthlyProfit[] = [
            //         'year' => $order->year,
            //         'month' => $order->month,
            //         'total' => $profit,
            //     ];
            // }

            // // Profit by quarter
            // $quarterlyProfit = [];
            // $quarterlyOrder = Order::where('order_status', 'delivered')
            // ->where('payment_status', 'paid')
            // ->whereBetween('order_created_at', [$startDate, $endDate])
            //     ->selectRaw('YEAR(order_created_at) as year, QUARTER(order_created_at) as quarter, sum(order_total_amount) as total')
            //     ->groupBy('year', 'quarter')
            //     ->get();

            // foreach ($quarterlyOrder as $order) {
            //     $importTotal = $monthlyImport->where('year', $order->year)
            //     ->whereIn('month', [($order->quarter - 1) * 3 + 1, ($order->quarter - 1) * 3 + 2, ($order->quarter - 1) * 3 + 3])
            //     ->sum('total') ?? 0;

            //     $profit = $order->total - $importTotal;
            //     $quarterlyProfit[] = [
            //         'year' => $order->year,
            //         'quarter' => $order->quarter,
            //         'total' => $profit,
            //     ];
            // }

            // // Profit by year
            // $yearlyImport = Import::whereBetween('import_created_at', [$startDate, $endDate])
            //     ->selectRaw('YEAR(import_created_at) as year, sum(import_total_amount) as total')
            //     ->groupBy('year')
            //     ->get();
            // $yearlyOrder = Order::where('order_status', 'delivered')
            // ->where('payment_status', 'paid')
            // ->whereBetween('order_created_at', [$startDate, $endDate])
            //     ->selectRaw('YEAR(order_created_at) as year, sum(order_total_amount) as total')
            //     ->groupBy('year')
            //     ->get();

            // $yearlyProfit = [];
            // foreach ($yearlyOrder as $order) {
            //     $importTotal = $yearlyImport->where('year', $order->year)->sum('total') ?? 0;
            //     $profit = $order->total - $importTotal;
            //     $yearlyProfit[] = [
            //         'year' => $order->year,
            //         'total' => $profit,
            //     ];
            // }



            // $data = [
            //     'total_profit' => $d,
            //     'daily_profit' => $dailyProfit,
            //     'monthly_profit' => $monthlyProfit,
            //     'quarterly_profit' => $quarterlyProfit,
            //     'yearly_profit' => $yearlyProfit,
            //     'profit_by_product' => $profitByProduct,
            // ];

            // return $this->responseSuccessWithData($temp, 'Lấy lợi nhuận thành công!', 200);

            return $this->responseSuccessWithData($profitByProduct, 'Lấy lợi nhuận thành công!', 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
}
