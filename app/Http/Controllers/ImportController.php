<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestAddImport;
use App\Services\ImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    protected ImportService $importService;
    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }
    public function add(RequestAddImport $request){
        return $this->importService->add($request);
    }
    
}
