<?php
namespace App\Services;

use App\Http\Requests\RequestUpdateCategory;
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
    public function update(RequestUpdateCategory $request,$id){
        DB::beginTransaction();
        try{
           $category = Category::where("category_id",$id)->first();
           if(empty($category)){
               return $this->responseError("Category không tồn tại trong hệ thống",404);
           }
           if($request->hasFile('category_thumbnail')){
               if($category->category_thumbnail){
                    $id_file = explode('.', implode('/', array_slice(explode('/', $category->category_thumbnail), 7)))[0];
                    Cloudinary::destroy($id_file);
               }
               $image = $request->file('category_thumbnail');
               $uploadFile = Cloudinary::upload($image->getRealPath(), [
                   'folder' => 'pbl6_pharmacity/thumbnail/category_thumbnail',
                   'resource_type' => 'auto'
               ]);
               $url = $uploadFile->getSecurePath();
               $data = array_merge($request->all(),['category_thumbnail'=>$url]);
               $category->update($data);
           }
           else{
                $request['category_thumbnail'] = $category->category_thumbnail;
                $category->update($request->all());
           }
            DB::commit();
            return $this->responseSuccessWithData($category, 'Cập nhật category thành công!', 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}