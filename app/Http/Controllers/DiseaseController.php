<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DiseaseService;
use App\Http\Requests\RequestDiseaseAdd;

class DiseaseController extends Controller
{
    protected DiseaseService $diseaseService;
    public function __construct(DiseaseService $diseaseService){
        $this->diseaseService = $diseaseService;
    }

    public function add(RequestDiseaseAdd $request){
        return $this->diseaseService->add($request);
    }


}
