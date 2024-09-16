<?php

namespace App\Services;

use App\Http\Requests\RequestCreateBrand;
use App\Http\Requests\RequestDeleteBrand;
use App\Http\Requests\RequestUpdateBrand;
use App\Models\Brand;
use App\Repositories\BrandInterface;
use App\Repositories\BrandRepository;
use Throwable;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class BrandService{
    use APIResponse;
    protected BrandInterface $brandRepository;
    public function __construct(BrandInterface $brandRepository){
        $this->brandRepository = $brandRepository;
    }
    public function add(RequestCreateBrand $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            if ($request->hasFile('brand_logo')) {
                $image = $request->file('brand_logo');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/thumbnail/brand_logo',
                    'resource_type' => 'auto'
                ]);
                $url = $uploadFile->getSecurePath();
                // Gán logo vào dữ liệu
                $data['brand_logo'] = $url;
            }

            // Tạo brand với đầy đủ dữ liệu
            $brand = Brand::create($data);
            // dd($brand);
            DB::commit();
            // Log::info('Brand creation successful. Returning response...');
            return $this->responseSuccessWithData($brand,'Thêm brand mới thành công!',201);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }

    }
    public function update(RequestUpdateBrand $request,$id){
        DB::beginTransaction();
        try{
            $brand = Brand::where("brand_id", $id)->first();
            if (!$brand) {
                return $this->responseError("Brand không tồn tại", 404);
            }
            if($request->hasFile('brand_logo')){
                if($brand->brand_logo){
                    $id_file = explode('.', implode('/', array_slice(explode('/', $brand->brand_logo), 7)))[0];
                    Cloudinary::destroy($id_file);
                }
                $image = $request->file('brand_logo');
                $uploadFile = Cloudinary::upload($image->getRealPath(),[
                    'folder' => 'pbl6_pharmacity/thumbnail/brand_logo',
                    'resource_type' => 'auto'
                ]);
                $url = $uploadFile->getSecurePath();
                $data = array_merge($request->all(), ['brand_logo' => $url]);
                $brand->update($data);
            }
            else{
                $request['brand_logo'] = $brand->brand_logo;
                $brand->update($request->all());
            }
            DB::commit();
            // Log::info("Update successful. Returning success response.");
            return $this->responseSuccessWithData($brand, "Cập nhật brand thành công!", 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            Log::error("Update failed: " . $e->getMessage());
            return $this->responseError($e->getMessage());
        }
    }
    public function get(Request $request,$id){
        try{
            $brand = Brand::where("brand_id", $id)->first();
            if(empty($brand)){
                return $this->responseError("Không tìm thấy brand",404);
            }
            return $this->responseSuccessWithData($brand, "Lấy thông tin brand thành công!",200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function delete(RequestDeleteBrand $request, $id){
        DB::beginTransaction();
        try{
            $brand = Brand::where("brand_id", $id)->first();
            if(empty($brand)){
                return $this->responseError("Không tìm thấy brand",404);
            }
            $brand->update(['brand_is_delete' =>$request->brand_is_delete]);
            DB::commit();
            return $this->responseSuccessWithData($brand, "Thay đổi trạng thái xoá của brand thành công!");
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e);
        }
    }
    public function getAll(Request $request){
        try{
            $orderBy = $request->typesort ?? 'brand_id';
            switch($orderBy){
                case 'brand_name':
                    $orderBy = 'brand_name';
                    break;
                case 'new':
                    $orderBy = "brand_id";
                    break;
                default:
                    $orderBy = 'brand_id';
                    break;
            }
            $orderDirection = $request->sortlatest ?? 'true';
            switch($orderDirection){
                case 'true':
                    $orderDirection = 'DESC';
                    break;
                default:
                    $orderDirection = 'ASC';
                    break;
            }
            $filter = (object) [
                'search' => $request->search ?? '',
                'brand_is_delete' => $request->brand_is_delete ?? 'all',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
            ];
            $brands = BrandRepository::getAll($filter);
            if(!(empty($request->paginate))){
                $brands = $brands->paginate($request->paginate);
            }
            else{
                $brands = $brands->get();
            }

            return $this->responseSuccessWithData($brands, "Lấy danh sách brand thành công!");
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    
}