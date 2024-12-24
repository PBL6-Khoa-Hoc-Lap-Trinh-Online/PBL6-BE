<?php

namespace App\Services;
use App\Http\Requests\RequestAddReview;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Review;
use App\Repositories\ReviewInterface;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReviewService{
    use APIResponse;
    protected ReviewInterface $reviewRepository;
    public function __construct(ReviewInterface $reviewRepository){
        $this->reviewRepository = $reviewRepository;
    }
    public function canReview(Request $request,$orderId,$productId)
    {
        $user = auth('user_api')->user();
        $order = Order::where('order_id',$orderId)->where('user_id',$user->user_id)->where('order_status','delivered')->first();
        if(!$order){
            return $this->responseError('Bạn không thể đánh giá đơn hàng này!',400);
        }
        $orderDetail = OrderDetail::where('order_id',$orderId)->where('product_id',$productId)->first();
        if(!$orderDetail){
            return $this->responseError("Sản phẩm $productId không tồn tại trong đơn hàng này!", 400);
        }
        $review = Review::where('order_id',$orderId)->where('product_id',$productId)->first();
        if($review){
            return $this->responseError("Sản phẩm $productId bạn đã đánh giá!", 400);
        }
        return $this->responseSuccess('Bạn có thể đánh giá sản phẩm này!',200);
    }
    public function addReview(RequestAddReview $request)
    {
        DB::beginTransaction();
        try{
            $user_id=auth('user_api')->user()->user_id;
            $orderId=$request->order_id;
            $productId=$request->product_id;
            $review = Review::where('order_id', $orderId)->where('product_id', $productId)->first();
            if ($review) {
                return $this->responseError("Sản phẩm $productId bạn đã đánh giá!", 400);
            }
            $data = $request->all();
            $data['user_id'] = $user_id;
            $imageUrls = [];
            if ($request->hasFile('review_images')) {
                $files = $request->file('review_images');
                if (!is_array($files)) {
                    // Nếu chỉ là một file, chuyển nó thành mảng
                    $files = [$files];
                }
                foreach ($files as $image) {
                    //upload image to cloudinary
                    $uploadFile = Cloudinary::upload($image->getRealPath(), [
                        'folder' => 'pbl6_pharmacity/thumbnail/product_image',
                        'resource_type' => 'auto',
                    ]);
                    //Add the url to the array
                    $imageUrls[] = $uploadFile->getSecurePath();
                }
                $data['review_images'] = $imageUrls;
            }
            $data['review_created_at'] = now();
            $review = Review::create($data);
            DB::commit();
            $data = $review;
            return $this->responseSuccessWithData($data, 'Đánh giá thành công!', 201);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function updateReview(RequestAddReview $request,$id){
        DB::beginTransaction();
        try{
            $review = Review::find($id);
            if(!$review){
                return $this->responseError('Đánh giá không tồn tại!',404);
            }
            $user_id = auth('user_api')->user()->user_id;
            $data = $request->all();
            $data['user_id'] = $user_id;
            $imageUrls = [];
            if ($request->hasFile('review_images')) {
                $files = $request->file('review_images');
                if (!is_array($files)) {
                    // Nếu chỉ là một file, chuyển nó thành mảng
                    $files = [$files];
                }
                if ($review->review_images) {
                    foreach ($review->review_images as $url) {
                        $id_file = explode('.', implode('/', array_slice(explode('/', $url), 7)))[0];
                        Cloudinary::destroy($id_file);
                    }
                }
                foreach ($files as $image) {
                    $uploadFile = Cloudinary::upload($image->getRealPath(), [
                        'folder' => 'pbl6_pharmacity/thumbnail/product_image',
                        'resource_type' => 'auto',
                    ]);
                    //Add the url to the array
                    $imageUrls[] = $uploadFile->getSecurePath();
                }
                $data = array_merge($request->all(), ['review_images' => $imageUrls]);
            } else {
                $request['review_images'] = $review->review_images;
                $review->update($request->all());
            }
            $data['review_updated_at'] = now();
            $review->update($data);
            DB::commit();
            $data = $review;
            return $this->responseSuccessWithData($data, 'Cập nhật đánh giá thành công!', 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function deleteReview($request,$id){
        DB::beginTransaction();
        try{
            $review = Review::find($id);
            if(!$review){
                return $this->responseError('Đánh giá không tồn tại!',404);
            }
            if($review->review_images){
                foreach($review->review_images as $url){
                    $id_file = explode('.', implode('/', array_slice(explode('/', $url), 7)))[0];
                    Cloudinary::destroy($id_file);
                }
            }
            $review->delete();
            DB::commit();
            return $this->responseSuccess('Xóa đánh giá thành công!', 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function getByProduct(Request $request,$productId){
       // $reviews = Review::where('product_id',$productId)->where('is_approved',1)->get();
        $reviews= $this->reviewRepository->getAll((object)['product_id'=>$productId,'is_approved'=>1]);
        if ($reviews->get()->isEmpty()) {
            return $this->responseError('Sản phẩm không có đánh giá!', 404);
        }

        if (!(empty($request->paginate))) {
            $data = $reviews->paginate($request->paginate);
        } else {
            $data = $reviews->get();
        }
        return $this->responseSuccessWithData($data, 'Lấy đánh giá sản phẩm thành công!', 200);
    }
    public function getByUser(Request $request){
        // $reviews = Review::where('user_id',$userId)->where('is_approved', 1)->get();
        // $user_id = auth('user_api')->user()->user_id;
        $userId=auth('user_api')->user()->user_id;
        dump($userId);
        $reviews= $this->reviewRepository->getAll((object)['user_id'=>$userId,'is_approved'=>1]);
        if ($reviews->get()->isEmpty()) {
            return $this->responseError('Bạn không có đánh giá nào!', 404);
        }

        if (!(empty($request->paginate))) {
            $data = $reviews->paginate($request->paginate);
        } else {
            $data = $reviews->get();
        }
        return $this->responseSuccessWithData($data, 'Lấy đánh giá người dùng thành công!', 200);
    }
    public function get(Request $request,$id){
        // $review = Review::where('review_id',$id)->where('is_approved', 1)->first();
        $review = $this->reviewRepository->getAll((object)['review_id'=>$id])->first();
        if(!$review){
            return $this->responseError('Đánh giá không tồn tại!',404);
        }
        $data = $review;
        return $this->responseSuccessWithData($data, 'Lấy đánh giá thành công!', 200);
    }
    public function getAll(Request $request){
        try {
            $orderBy = $request->typesort ?? 'review_id';
            switch ($orderBy) {
                case 'order_id':
                    $orderBy = "order_id";
                    break;
                case 'review_rating':
                    $orderBy = "review_rating";
                    break;
                case 'user_id':
                    $orderBy = "user_id";
                    break;
                case 'product_id':
                    $orderBy = "product_id";
                    break;
                case 'new':
                    $orderBy = "review_id";
                    break;
                default:
                    $orderBy = 'review_id';
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
                'is_approved' => $request->is_approved ?? 'all',
                'review_id' => $request->review_id ?? '',
                'user_id' => $request->user_id ?? '',
                'product_id' => $request->product_id ?? '',
                'order_id'=>$request->order_id ?? '',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
            ];
            $reviews=$this->reviewRepository->getAll($filter);
            if($reviews->get()->isEmpty()){
                return $this->responseError('Không có đánh giá!',404);
            }
            
            if (!(empty($request->paginate))) {
                $data = $reviews->paginate($request->paginate);
            } else {
                $data = $reviews->get();
            }
            return $this->responseSuccessWithData($data, 'Lấy tất cả đánh giá thành công!', 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
       
    }
    public function hiddenReview(Request $request,$id){
        DB::beginTransaction();
        try{
            $review = Review::find($id);
            if(!$review){
                return $this->responseError('Đánh giá không tồn tại!',404);
            }
            $review->is_approved = !$review->is_approved;
            $review->save();
            DB::commit();
            $message=$review->is_approved ? 'Hiện đánh giá thành công!' : 'Ẩn đánh giá thành công!';
            $data = $review;
            return $this->responseSuccessWithData($data, $message, 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}