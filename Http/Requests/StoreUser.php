<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Rules\Recaptcha;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Modules\Authentication\Models\Verification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StoreUser extends FormRequest
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


            'first_name' => 'required|regex:/(^[a-zA-Z ]*$)/',
            'last_name' => 'required|regex:/(^[a-zA-Z ]*$)/',
            'email' =>'email:rfc,dns|required',
            'password' =>   ['required', 'confirmed','max:35',
                                                Password::min(8)
                                                ->mixedCase()
                                                ->letters()
                                                ->numbers()
                                                ->symbols()
                                                ->uncompromised()


                            ],
            'acceptPincode'=>'required','true',
            'acceptTerms'=>'required','true',
            // 'g-recaptcha-response' => ['required', new Recaptcha]
        ];
    }


    public function messages()
    {
        return [
            'first_name.required' => "first Name is required",
            'first_name.regex' => "first Name does not accept numbers or symbols ",
            'last_name.required' => "last Name is required",
            'last_name.regex' => "last Name does not accept numbers or symbols",
            'password.required' => "password is required",
            'password.confirmed' => "password & confirm password are not similar",
            'acceptTerms.required' => "Please agree the MSP Terms & Conditions",
            'acceptPincode.required' => "Please agree receiving authentication code to verify your email",
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
