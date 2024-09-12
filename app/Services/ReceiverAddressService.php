<?php 
namespace App\Services;

use App\Http\Requests\RequestAddReceiverAddress;
use App\Models\ReceiverAddress;
use App\Repositories\ReceiverAddressInterface;
use App\Traits\APIResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReceiverAddressService
{
    use APIResponse;
    protected ReceiverAddressInterface $receiverAddressRepository;
    public function __construct(ReceiverAddressInterface $receiverAddressRepository)
    {
        $this->receiverAddressRepository = $receiverAddressRepository;
    }
    public function add(RequestAddReceiverAddress $request)
    {
        DB::beginTransaction();
        try{
            $id_user = auth('user_api')->user()->user_id;
            $data =array_merge($request->all(),['user_id'=>$id_user]);
            $receiverAddress= ReceiverAddress::create($data);
            DB::commit();
            return $this->responseSuccessWithData($receiverAddress,'Thêm địa chỉ nhận hàng thành công!', 201);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }

}