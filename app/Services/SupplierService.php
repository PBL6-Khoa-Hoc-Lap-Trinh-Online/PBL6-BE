<?php 
namespace App\Services;
use App\Http\Requests\RequestCreateSupplier;
use App\Http\Requests\RequestUpdateSupplier;
use App\Models\Supplier;
use App\Repositories\SupplierInterface;
use App\Traits\APIResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class SupplierService{
    use APIResponse;
    protected SupplierInterface $supplierRepository;
    public function __construct(SupplierInterface $supplierRepository){
        $this->supplierRepository = $supplierRepository; 
    }
    public function add(RequestCreateSupplier $request){
        DB::beginTransaction();
        try{
            $supplier = Supplier::create($request->all());
            DB::commit();
            return $this->responseSuccessWithData($supplier, "Thêm nhà cung cấp mới thành công!!",201);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function update(RequestUpdateSupplier $request,$id){
        DB::beginTransaction();
        try{
            $supplier = Supplier::where("supplier_id", $id)->first();
            if(empty($supplier)){
                return $this->responseError("Nhà cung cấp không tồn tại", 404);
            }
            $supplier->update($request->all());
            DB::commit();
            return $this->responseSuccessWithData($supplier, "Cập nhật nhà cung cấp thành công!!",200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}