<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Traits\APIResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
class RequestUpdateDeliveryMethod extends FormRequest
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
        $id=$this->route('id');
        return [
            'delivery_method_name' => ['required', 'string','max:100', Rule::unique('delivery_methods')->ignore($id, 'delivery_method_id')],
            'delivery_fee' => ['required', 'numeric', 'bail', 'regex:/^\d+(\.\d{1,2})?$/'],
            'delivery_method_description' => 'string',
            'delivery_method_logo' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
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
