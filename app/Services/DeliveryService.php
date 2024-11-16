<?php
namespace App\Services;

use App\Http\Requests\RequestAddDeliveryMethod;
use App\Http\Requests\RequestUpdateDeliveryMethod;
use App\Models\Delivery;
use App\Models\DeliveryMethod;
use App\Repositories\DeliveryMethodRepository;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class DeliveryService{
    use APIResponse;
    public function add(RequestAddDeliveryMethod $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            if ($request->hasFile('delivery_method_logo')) {
                $image = $request->file('delivery_method_logo');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/thumbnail/brand_logo',
                    'resource_type' => 'auto'
                ]);
                $url = $uploadFile->getSecurePath();
                // Gán logo vào dữ liệu
                $data['delivery_method_logo'] = $url;
            }
            $delivery_method = DeliveryMethod::create($data);
            DB::commit();
            return $this->responseSuccessWithData($delivery_method, "Thêm mới phương thức thành công!", 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function getDeliveryMethod(Request $request, $id)
    {
        try {
            $delivery_method = DeliveryMethod::find($id);
            if (!$delivery_method) {
                return $this->responseError("Không tìm thấy phương thức thanh toán!", 404);
            }
            return $this->responseSuccessWithData($delivery_method, "Lấy thông tin phương thức thanh toán thành công!", 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function update(RequestUpdateDeliveryMethod $request, $id)
    {
        DB::beginTransaction();
        try {
            $delivery_method = DeliveryMethod::find($id);
            if (!$delivery_method) {
                return $this->responseError("Không tìm thấy phương thức thanh toán!", 404);
            }
            if ($request->hasFile('delivery_method_logo')) {
                if ($delivery_method->delivery_method_logo) {
                    $id_file = explode('.', implode('/', array_slice(explode('/', $delivery_method->delivery_method_logo), 7)))[0];
                    Cloudinary::destroy($id_file);
                }
                $image = $request->file('delivery_method_logo');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/thumbnail/brand_logo',
                    'resource_type' => 'auto'
                ]);
                $url = $uploadFile->getSecurePath();
                $data = array_merge($request->all(), ['delivery_method_logo' => $url]);
                $delivery_method->update($data);
            } else {
                $request['delivery_method_logo'] = $delivery_method->delivery_method_logo;
                $delivery_method->update($request->all());
            }
            DB::commit();
            return $this->responseSuccessWithData($delivery_method, "Cập nhật phương thức thanh toán thành công!", 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function delete(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $delivery_method = DeliveryMethod::find($id);
            if (!$delivery_method) {
                return $this->responseError("Không tìm thấy phương thức thanh toán!", 404);
            }
            $status = !$delivery_method->delivery_is_active;
            $delivery_method->update(['delivery_is_active' => $status]);
            $message = $status ? "Khôi phục phương thức thanh toán thành công!" : "Xóa phương thức thanh toán thành công!";
            DB::commit();
            return $this->responseSuccess($message, 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function getDeliveryMethods(Request $request)
    {
        $orderBy = $request->typesort ?? 'delivery_method_id';
        switch ($orderBy) {
            case 'delivery_method_name':
                $orderBy = 'delivery_method_name';
                break;
            case 'new':
                $orderBy = "delivery_method_id";
                break;
            default:
                $orderBy = 'delivery_method_id';
                break;
        }
        $orderDirection = $request->sortlatest ?? 'true';
        switch ($orderDirection) {
            case 'true':
                $orderDirection = 'DESC';
                break;
            default:
                $orderDirection = 'ASC';
                break;
        }
        $filter = (object) [
            'search' => $request->search ?? '',
            'delivery_is_active' => $request->delivery_is_delete ?? 'all',
            'orderBy' => $orderBy,
            'orderDirection' => $orderDirection,
        ];
        $deliveryMethods = DeliveryMethodRepository::getAll($filter);
        if (!(empty($request->paginate))) {
            $deliveryMethods = $deliveryMethods->paginate($request->paginate);
        } else {
            $deliveryMethods = $deliveryMethods->get();
        }
        return $deliveryMethods;
    }
    public function getAllDeliveryMethodByUser(Request $request)
    {
        try {
            $delivery_methods = $this->getDeliveryMethods($request)->where('delivery_is_active', 1)->values();
            return $this->responseSuccessWithData($delivery_methods, "Lấy danh sách phương thức thanh toán thành công!", 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function getAllDeliveryMethodByAdmin(Request $request)
    {
        try {
            $delivery_methods = $this->getDeliveryMethods($request)->values();
            return $this->responseSuccessWithData($delivery_methods, "Lấy danh sách phương thức thanh toán thành công!", 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }

    public function getAll(Request $request){
        try{
            $deliveries = Delivery::all();
            return $this->responseSuccessWithData($deliveries,"Get all deliveries successfully",200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
}