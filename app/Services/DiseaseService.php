<?php

namespace App\Services;
use App\Traits\APIResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Disease;

use App\Repositories\DiseaseInterface;
use App\Repositories\DiseaseRepository;

use App\Http\Requests\RequestDiseaseAdd;


class DiseaseService
{
    use APIResponse;
    protected DiseaseInterface $diseaseRepository;
    public function __construct(DiseaseInterface $diseaseRepository){
        $this->diseaseRepository = $diseaseRepository;
    }

    public function add(RequestDiseaseAdd $request){
        DB::beginTransaction();
        try {
            $data = $request->all();
            $disease = Disease::create($data);
            DB::commit();
            return $this->responseSuccess('Thêm bệnh mới thành công', 201);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }

}
