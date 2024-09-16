<?php 
namespace App\Services;
use App\Http\Requests\RequestCreateSupplier;
use App\Http\Requests\RequestDeleteSupplier;
use App\Http\Requests\RequestUpdateSupplier;
use App\Models\Supplier;
use App\Repositories\SupplierInterface;
use App\Repositories\SupplierRepository;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
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
    public function get($request,$id){
        try{
            $supplier = Supplier::where("supplier_id", $id)->first();
            if(empty($supplier)){
                return $this->responseError("Nhà cung cấp không tồn tại", 404);
            }
            return $this->responseSuccessWithData($supplier, "Lấy thông tin nhà cung cấp thành công!!",200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function delete(RequestDeleteSupplier $request,$id){
        DB::beginTransaction();
        try{
            $supplier = Supplier::where("supplier_id", $id)->first();
            if(empty($supplier)){
                return $this->responseError("Nhà cung cấp không tồn tại", 404);
            }
            $supplier->update(['supplier_is_delete' => $request->supplier_is_delete]);
            DB::commit();
            $request->supplier_is_delete == 1 ? $message = "Xoá nhà cung cấp thành công!!" : $message = "Khôi phục nhà cung cấp thành công!!";
            return $this->responseSuccess($message, 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function getAll(Request $request){
        try{
            $orderBy = $request->typesort ?? 'supplier_id';
            switch ($orderBy) {
                case 'supplier_name':
                    $orderBy = 'supplier_name';
                    break;
                case 'contact_person':
                    $orderBy = 'contact_person';
                    break;
                case 'supplier_address':
                    $orderBy = 'supplier_address';
                    break;
                case 'supplier_phone':
                    $orderBy = 'supplier_phone';
                    break;
                case 'new':
                    $orderBy = "supplier_id";
                    break;
                default:
                    $orderBy = 'supplier_id';
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
                'supplier_is_delete' => $request->supplier_is_delete ?? 'all',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
            ];
            $suppliers = SupplierRepository::getAll($filter);
            if (!(empty($request->paginate))) {
                $suppliers = $suppliers->paginate($request->paginate);
            } else {
                $suppliers = $suppliers->get();
            }

            return $this->responseSuccessWithData($suppliers, "Lấy danh sách nhà cung cấp thành công!");
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
}