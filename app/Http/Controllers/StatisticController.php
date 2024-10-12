<?php

namespace App\Http\Controllers;

use App\Services\StatisticService;
use App\Traits\APIResponse;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    protected StatisticService $statisticService;
    public function __construct(StatisticService $statisticService)
    {
        $this->statisticService = $statisticService;
    }
    public function getRevenue(Request $request)
    {
        return $this->statisticService->getRevenue($request);
    }
}
