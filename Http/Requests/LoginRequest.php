<?php

namespace Modules\Authentication\Http\Requests;

use App\Http\Traits\ApiResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Password;
use App\Rules\Recaptcha;

class LoginRequest extends FormRequest
{
    use ApiResponseTrait;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if(in_array(Route::currentRouteName(),['user.login.2fa','user.change.2fa.status'])){
            return [
                'email'            => ['required' , 'email' , 'exists:users,email'],
                'google2fa_secret' => ['required']
            ];
        } else {
            return [
                'email'                => ['required' , 'email' , 'exists:users,email'],
                'password'             => ['required', Password::min(6)],
                // 'g-recaptcha-response' => ['required', new Recaptcha]
            ];
        }

    }

    // custom message
    public function messages()
    {
        return [
            'email.exists' => "Mail and password don't match any of system accounts"
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $this->apiResponseValidation($validator);
    }
}
