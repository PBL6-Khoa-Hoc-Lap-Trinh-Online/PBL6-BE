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
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
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
            set_time_limit(500);
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
                        'resource_type' => 'auto',
                        // 'upload_preset' => 'product_image',
                        //  'timeout'=>300,
                        
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

    public function get(Request $request,$id){
        try{
            $product = Product::find($id);
            if(empty($product)){
                return $this->responseError("Sản phẩm không tồn tại!",404);
            }
            return $this->responseSuccessWithData($product, "Lấy sản phẩm thành công!");
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function getAll(Request $request){
        try{
            $orderBy = $request->typesort ?? 'products.product_id';
            switch($orderBy){
                case 'product_name':
                    $orderBy = 'products.product_name';
                    break;
                case 'product_price':
                    $orderBy = 'products.product_price';
                    break;
                case 'product_sold':
                    $orderBy = 'products.product_sold';
                    break;
                case 'product_quantity':
                    $orderBy = 'products.product_quantity';
                    break;
                case 'new':
                    $orderBy='products.product_id';
                    break;
                default:
                    $orderBy = 'products.product_id';
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
                'category_name' => $request->category_name ?? '',
                'brand_name' =>$request->brand_name ?? '',
                'product_price'=>$request->product_price ??'',
                'price_min' =>$request->price_min ?? '',
                'price_max' =>$request->price_max ?? '',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
                'product_is_delete' =>$request->is_delete ?? 'both',
            ];
            if(!(empty($request->paginate))){
                $products = $this->productRepository->getAll($filter)->paginate($request->paginate);
            }
            else{
                $products = $this->productRepository->getAll($filter)->get();
            }
            return $this->responseSuccessWithData($products, 'Danh sách sản phẩm!', 200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function addUploadS3(RequestAddProduct $request){
        DB::beginTransaction();
        try{
            $data = $request->all();
            $imageUrls = [];
            if ($request->hasFile('product_images')) {
                $files = $request->file('product_images');
                if (!is_array($files)) {
                    // Nếu chỉ là một file, chuyển nó thành mảng
                    $files = [$files];
                }
                foreach ($files as $file) {
                    $name = time() . $file->getClientOriginalName();
                    $filePath = 'product_image/' . $name;

                    // Upload file to S3 và gán vào biến riêng $uploadSuccess
                    $uploadSuccess = Storage::disk('s3')->put($filePath, file_get_contents($file));

                    // Kiểm tra nếu upload thành công
                    if ($uploadSuccess) {
                        // Set ACL to public-read
                        Storage::disk('s3')->setVisibility($filePath, 'public');

                        // Lấy URL của file
                        $url = Storage::disk('s3')->url($filePath);

                        // Thêm URL vào mảng $imageUrls
                        $imageUrls[] = $url;
                    }
                }

                $data['product_images'] = $imageUrls;
                // $data['product_images'] = json_encode($imageUrls, JSON_UNESCAPED_SLASHES);
            }
            $product = Product::create($data);
            DB::commit();
            return $this->responseSuccessWithData($product, 'Thêm sản phẩm mới thành công!', 201);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}