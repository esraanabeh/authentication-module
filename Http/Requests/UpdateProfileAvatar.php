<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;


class UpdateProfileAvatar extends FormRequest
{

    use  ApiResponseTrait;
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
     * @return array
     */
    public function rules()
    {
        return [
            'avatar' =>  'required|image|mimes:jpg,png,jpeg|max:1048',
        ];
    }

    // custome message
    public function messages()
    {
        return [
            'avatar.required' => "The Profile image is required",
            'avatar.image' => "The Profile image must be an image",
            'avatar.max' => "The Profile image size must be less than 1 mega",
            'avatar.mimes' => "The Profile image must be a file of type: jpg, png ,jpeg",
        ];
    }




    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $this->apiResponseValidation($validator);
    }
}
