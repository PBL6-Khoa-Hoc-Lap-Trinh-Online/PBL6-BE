<?php

namespace App\Http\Requests;

use App\Traits\APIResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
class RequestUserBuyProduct extends FormRequest
{
    use APIResponse;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
             "receiver_address_id"=>'required|exists:receiver_addresses,receiver_address_id',
             "payment_id"=>'required|exists:payments,payment_id',
             "delivery_id"=>'required|exists:deliveries,delivery_id',
             "order_details"=>'required|array',
             "order_details.*.product_id"=>'required|exists:products,product_id',
             "order_details.*.order_quantity"=>'required|int|min:1',
        ];
    }
    public function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();

        return $this->responseErrorValidate($errors, $validator);
    }
    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'body.required' => 'Body is required',
        ];
    }
}