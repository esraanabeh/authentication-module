<?php

namespace Modules\Authentication\Http\Controllers\API;

use App\Http\Traits\ApiResponseTrait;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authentication\Http\Requests\ForgetPassword;
use Modules\Authentication\Http\Requests\ResetPassworRequest;
use Modules\Authentication\Repositories\Interfaces\IPasswordRepository;
use Modules\Authentication\Repositories\Interfaces\IUserRepository;


class ForgetPasswordController extends Controller
{
    use ApiResponseTrait;
    public function __construct(IUserRepository $user , IPasswordRepository $iPasswordRepository)
    {
        $this->user = $user;

        $this->iPasswordRepository=$iPasswordRepository;
    }

    public function forgetPassword(ForgetPassword $request)
    {
        $result = $this->iPasswordRepository->sendResetLinkEmail($request->validated());
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }

    public function resetPassword(ResetPassworRequest $request)
    {
        $result = $this->iPasswordRepository->resetPassword($request->validated());
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }

}
