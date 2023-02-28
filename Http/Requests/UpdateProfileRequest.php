<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Rules\PhoneNumber;
use Illuminate\Support\Facades\Auth;


class UpdateProfileRequest extends FormRequest
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
            'first_name' => 'required',
            'last_name' => 'required',
            'phone_number'=>['required',new PhoneNumber()],
            'email' =>'email:rfc,dns|unique:users,email,'.Auth::user()->id.",id",
            //'avatar' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
        ];
    }

    // custome message
    public function messages()
    {
        return [
            'avatar.required' => "The Profile image is required",
            'avatar.image' => "The Profile image must be an image",
            'avatar.max' => "The Profile image size must be less than 2 mega",
            'avatar.mimes' => "The Profile image must be a file of type: jpg, png, jpeg, gif, svg",
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
