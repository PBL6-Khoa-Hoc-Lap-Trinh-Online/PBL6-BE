<?php 
namespace App\Services;

use App\Http\Requests\RequestAddImport;
use App\Models\Import;
use App\Models\ImportDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Repositories\ImportInterface;
use App\Traits\APIResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportService{
    use APIResponse;
    protected ImportInterface $importRepository;
    public function __construct(ImportInterface $importRepository){
        $this->importRepository = $importRepository;
    }
    public function add(RequestAddImport $request){
        DB::beginTransaction();
        try{
            $data = [
                'supplier_id' => $request->supplier_id,
                'import_date' => now(),
                'import_total_amount' => 0.00,
            ];
            $import = Import::create($data);
            $importTotal = 0;
            $importDetails = [];
            foreach($request->import_details as $importDetail){
                $detail = [
                    'import_id' => $import->import_id,
                    'product_id' => $importDetail['product_id'],
                    'import_quantity' => $importDetail['import_quantity'],
                    'import_price' => $importDetail['import_price'],
                    'product_total_price' => $importDetail['import_quantity'] * $importDetail['import_price'],
                    'product_expiry_date' => $importDetail['product_expiry_date'],
                ];
                $product = Product::find($importDetail['product_id']);
                $product->update([
                    'product_quantity' => $product->product_quantity + $importDetail['import_quantity'],
                ]);
                $importTotal += $detail['product_total_price'];
                $import_detail = ImportDetail::create($detail);
                $importDetails[] = $import_detail;
            }
            $import->update(['import_total_amount' => $importTotal]);
            DB::commit();
            $data = [
                'import' => $import,
                'import_details' => $importDetails,
            ];
            return $this->responseSuccessWithData($data,'Nhập kho thành công!',200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}