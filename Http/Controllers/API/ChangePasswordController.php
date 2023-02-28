<?php

namespace Modules\Authentication\Http\Controllers\API;


use Illuminate\Routing\Controller;
use Modules\Authentication\Repositories\Interfaces\IUserRepository;
use App\Http\Traits\ApiResponseTrait;
use Modules\Authentication\Http\Requests\ChangePasswordRequest;

class ChangePasswordController extends Controller
{
    use ApiResponseTrait;
    public function __construct(IUserRepository $user)
    {
        $this->user = $user;
    }

    public function changePassword(ChangePasswordRequest $request){
        $result= $this->user->updatePassword($request->post('old_password'),$request->post('new_password'));
        return $this->apiResponse($result['data'] , $result['status'] , $result['identifier_code'] , $result['status_code'] , $result['message']);
    }

}
