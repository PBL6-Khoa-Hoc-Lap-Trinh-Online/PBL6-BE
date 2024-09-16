<?php
namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Repositories\CategoryInterface;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryService{
    use APIResponse;
    protected CategoryInterface $categoryRepository;
    public function __construct(CategoryInterface $categoryRepository){
        $this->categoryRepository = $categoryRepository;
    }
    public function add($request){
        DB::beginTransaction();
        try{
            $data = $request->all();
            if($request->hasFile('category_thumbnail')){
                $image = $request->file('category_thumbnail');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/thumbnail/category_thumbnail',
                    'resource_type' => 'auto'
                ]);
                $url = $uploadFile->getSecurePath();
                $data['category_thumbnail'] = $url;
            }
            $category = Category::create($data);
            DB::commit();
            return $this->responseSuccessWithData($category, 'Thêm category mới thành công!', 201);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}