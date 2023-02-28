<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Rules\Recaptcha;



class ForgetPassword extends FormRequest
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
            'email' => ['required' , 'email:rfc,dns','exists:users,email'],
            // 'g-recaptcha-response' => ['required', new Recaptcha]
        ];
    }

    // custom message
    public function messages()
    {
        return [
            'email.exists' => "Mail doesn't match any of system accounts"
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
