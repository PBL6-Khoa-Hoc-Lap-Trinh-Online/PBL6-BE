<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RequestDiseaseAdd extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'disease_name' => 'required|string|max:255',
            'disease_thumbnail' => 'nullable|image|max:2048',
            'general_overview' => 'required|string',
            'symptoms' => 'required|string',
            'cause' => 'required|string',
            'risk_subjects' => 'required|string',
            'diagnosis' => 'required|string',
            'prevention' => 'required|string',
            'treatment_method' => 'required|string',
            'disease_is_delete' => 'boolean',
            'disease_is_show' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'disease_name.required' => 'The disease name is required.',
            'disease_name.string' => 'The disease name must be a string.',
            'disease_name.max' => 'The disease name may not be greater than 255 characters.',
            'disease_thumbnail.image' => 'The thumbnail must be an image.',
            'disease_thumbnail.max' => 'The thumbnail may not be greater than 2MB.',
            'general_overview.required' => 'The general overview is required.',
            'symptoms.required' => 'The symptoms are required.',
            'cause.required' => 'The cause is required.',
            'risk_subjects.required' => 'The risk subjects are required.',
            'diagnosis.required' => 'The diagnosis is required.',
            'prevention.required' => 'The prevention is required.',
            'treatment_method.required' => 'The treatment method is required.',
            'disease_is_delete.boolean' => 'The delete status must be true or false.',
            'disease_is_show.boolean' => 'The show status must be true or false.',
        ];
    }
}
