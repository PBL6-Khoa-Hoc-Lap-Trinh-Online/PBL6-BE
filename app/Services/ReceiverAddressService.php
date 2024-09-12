<?php 
namespace App\Services;

use App\Http\Requests\RequestAddReceiverAddress;
use App\Models\ReceiverAddress;
use App\Models\User;
use App\Repositories\ReceiverAddressInterface;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
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
    public function getAddress(Request $request,$id){
        try{
            $user_id = auth('user_api')->user()->user_id;
            $user= User::find($user_id);
            if($user){
                $receiver_address = ReceiverAddress::where('receiver_address_id',$id)->where('user_id',$user_id)->first();
                if($receiver_address){
                    return $this->responseSuccessWithData($receiver_address,'Lấy địa chỉ nhận hàng thành công!', 200);
                }
                else{
                    return $this->responseError('Không tìm thấy địa chỉ nhận hàng!');
                }
            }
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }

}