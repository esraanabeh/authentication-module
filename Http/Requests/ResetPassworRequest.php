<?php

namespace Modules\Authentication\Http\Requests;

use App\Http\Traits\ApiResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Password;

class ResetPassworRequest extends FormRequest
{
    use ApiResponseTrait;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'token' => ['required'],
            'email' => ['required' , 'email:rfc,dns','exists:users,email'],
            'password' =>   ['required', 'confirmed',
                                                    Password::min(8)
                                                    ->mixedCase()
                                                    ->letters()
                                                    ->numbers()
                                                    ->symbols()
                                                    ->uncompromised()
                            ],
        ];
    }

    // custome message
    public function messages()
    {
        return [
            'email.exists' => "Mail doesn't match any of system accounts",
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
