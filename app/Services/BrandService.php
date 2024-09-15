<?php

namespace App\Services;

use App\Http\Requests\RequestCreateBrand;
use App\Models\Brand;
use App\Repositories\BrandInterface;
use Throwable;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;

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

            // Kiểm tra và upload logo nếu có
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

            DB::commit();
            return $this->responseSuccessWithData($brand, 'Thêm brand mới thành công!', 201);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }

    }
    
}