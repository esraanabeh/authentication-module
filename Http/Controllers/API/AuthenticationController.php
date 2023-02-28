<?php

namespace Modules\Authentication\Http\Controllers\API;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Modules\Authentication\Repositories\Interfaces\IUserRepository;
use Modules\Authentication\Http\Resources\UserResource;
use Modules\Authentication\Http\Resources\ProfileResource;
use Modules\Authentication\Http\Requests\StoreUser;
use Modules\Authentication\Http\Requests\ForgetPassword;
use Modules\Authentication\Http\Requests\CahngePassword;
use Modules\Authentication\Http\Requests\ResetPassword;
use Modules\Authentication\Http\Requests\UpdateProfileRequest;
use Modules\Authentication\Http\Requests\PinCode;
use Modules\Authentication\Notifications\SendAdminVerificationMail;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Modules\Authentication\Jobs\SendAdminVerificarionMailJob;
use Modules\Authentication\Jobs\SendForgetPasswordCodeMailJob;
use Modules\Authentication\Notifications\SendForgetPasswordCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Models\Verification;
use Illuminate\Support\Facades\Crypt;
use Modules\Authentication\Http\Requests\LoginRequest;
use Modules\Authentication\Http\Requests\ResendPinCodeRequest;
use Modules\Authentication\Http\Requests\ResetPasswordRequest;

class AuthenticationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(IUserRepository $user)
    {
        $this->user = $user;
    }

    public function register(StoreUser $request)
    {
        $result = $this->user->register($request);
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }

    public function resendPinCode(ResendPinCodeRequest $request)
    {
        $user = User::where('email' ,$request->email)->first();
       dispatch(new SendAdminVerificarionMailJob($user,$this->user));
        return $this->apiResponse(null , true,142001,200,'code sent successfully');
    }


    public function verifyCode(PinCode $request){
        $result = $this->user->mailVerification($request);
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }


    public function login(LoginRequest $request)
    {
        $result = $this->user->login($request->validated());
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }

    public function completeLogin (LoginRequest $request)
    {
        $result = $this->user->verify2FAstep($request->validated());
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }

    public function logout()
    {
        auth()->user()->tokens()->where('name','API Token')->delete();

        return $this->apiResponse( null,true,103001,200,'logout success' );
    }



    public function changePassword(Request $request){
        $user= $this->user->updatePassword($request->post('password'));
        return $this->apiResponse( new UserResource(auth()->user()),'true','106001',200,'sucessfuly' );
    }

    public function confirmChangePassword(Request $request){
        $result = $this->user->confirmChangePassword($request);
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }
    public function twoFAStatus()
    {
        return $this->apiResponse(auth()->user()->allow_2fa,true,121001,200,'2fa status');
    }

    public function requestToChange2FAStatus()
    {
        $qrCode = $this->user->generate2FAcode(auth()->user());
        return $this->apiResponse(['email' => auth()->user()->email, 'qr_code' => $qrCode],true,122001,200,'2fa authentication is required');
    }

    public function change2FAStatus(LoginRequest $request)
    {
         $result = $this->user->update2FAStatus($request->validated());
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }



}
