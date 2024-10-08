<?php

namespace App\Services;

use Kjmtrue\VietnamZone\Models\Province;
use Kjmtrue\VietnamZone\Models\District;
use Kjmtrue\VietnamZone\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\APIResponse;

use Illuminate\Http\Request;
use Throwable;

class VietnamZoneService
{
    use APIResponse;
    public function getProvinces()
    {
        try {
            $province = Province::all();
            return $this->responseSuccessWithData($province,'Lấy dữ liệu tỉnh/thành phố thành công',200);
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }

    public function getDistrictsByProvinceId($provinceId)
    {
        try {
            $district = District::whereProvinceId($provinceId)->get();
            return $this->responseSuccessWithData($district,'Lấy dữ liệu huyện/quận thành công',200);
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }

    public function getWardsByDistrictId($districtId)
    {
        try {
            $ward = Ward::whereDistrictId($districtId)->get();
            return $this->responseSuccessWithData($ward,'Lấy dữ liệu xã/phường/thị trấn thành công',200);
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }
}
