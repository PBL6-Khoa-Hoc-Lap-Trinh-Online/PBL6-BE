<?php
namespace App\Services;

use App\Http\Requests\RequestAddProduct;
use App\Jobs\UploadImage;
use App\Models\Product;
use App\Repositories\ProductInterface;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductService{
    use APIResponse;
    protected ProductInterface $productRepository;
    public function __construct(ProductInterface $productRepository){
        $this->productRepository = $productRepository;
    }
    public function add(RequestAddProduct $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            $imageUrls = [];
            if ($request->hasFile('product_images')) {
                $files = $request->file('product_images');
                if (!is_array($files)) {
                    // Nếu chỉ là một file, chuyển nó thành mảng
                    $files = [$files];
                }
                foreach ($files as $image) {
                    //upload image to cloudinary
                    $uploadFile = Cloudinary::upload($image->getRealPath(), [
                        'folder' => 'pbl6_pharmacity/thumbnail/product_image',
                        'resource_type' => 'auto'
                    ]);
                    //Add the url to the array
                    $imageUrls[] = $uploadFile->getSecurePath();
                }
                $data['product_images'] = $imageUrls;
                // $data['product_images'] = json_encode($imageUrls, JSON_UNESCAPED_SLASHES);
                
                // dd($data);
            }
            $product = Product::create($data);
            DB::commit();
            return $this->responseSuccessWithData($product, 'Thêm sản phẩm mới thành công!', 201);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}