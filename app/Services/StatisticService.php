<?php
namespace App\Services;

use App\Models\Order;
use App\Traits\APIResponse;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatisticService
{
    use APIResponse;
    public function getRevenue(Request $request)
    {
        try{
            $startDate = $request->input('start_date',Carbon::now()->startOfMonth());
            $endDate = $request->input('end_date',Carbon::now()->endOfMonth());
            //Tổng doang thu
            $totalRevenue = Order::where('order_status','delivered')
                ->where('payment_status','paid')
                ->whereBetween('order_created_at',[$startDate,$endDate])
                ->selectRaw('sum(order_total_amount) as total')
                ->first();
            //Doanh thu theo từng ngày
            $dailyRevenue = Order::where('order_status','delivered')->whereBetween('order_created_at',[$startDate,$endDate])
                ->selectRaw('DATE(order_created_at) as date, sum(order_total_amount) as total')
                ->groupBy('date')
                ->get();
            //Doanh thu theo từng tháng
            $monthlyRevenue = Order::where('order_status','delivered')->whereBetween('order_created_at',[$startDate,$endDate])
                ->selectRaw('YEAR(order_created_at) as year, MONTH(order_created_at) as month, sum(order_total_amount) as total')
                ->groupBy('year','month')
                ->get();
            //Doanh thu theo từng năm
            $yearlyRevenue = Order::where('order_status','delivered')->whereBetween('order_created_at',[$startDate,$endDate])
                ->selectRaw('YEAR(order_created_at) as year, sum(order_total_amount) as total')
                ->groupBy('year')
                ->get();

            //Doanh thu theo từng sản phẩm
            $revenueByProduct = Order::where('order_status','delivered')->whereBetween('order_created_at',[$startDate,$endDate])
                ->join('order_details','orders.order_id','=','order_details.order_id')
                ->selectRaw('product_id, sum(order_details.order_price * order_details.order_quantity) as total')
                ->groupBy('product_id')
                ->get();
            $data = [
                'total_revenue' => $totalRevenue,
                'daily_revenue' => $dailyRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'yearly_revenue' => $yearlyRevenue,
                'revenue_by_product' => $revenueByProduct
            ];
            return $this->responseSuccessWithData($data,'Lấy doanh thu thành công!', 200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
}