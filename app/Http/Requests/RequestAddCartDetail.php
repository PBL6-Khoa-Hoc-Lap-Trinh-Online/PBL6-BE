<?php

namespace App\Http\Requests;

use App\Traits\APIResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class RequestAddCartDetail extends FormRequest
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
            // 'cart_id' => 'required|exists:carts,cart_id',
            'product_id' => 'required|exists:products,product_id',
            'cart_quantity' => 'required|integer|min:1',
            // 'cart_price' => ['required', 'numeric', 'bail', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     */
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
