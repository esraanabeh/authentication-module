<?php

namespace Modules\Authentication\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authentication\Repositories\Interfaces\IUserRepository;
use Modules\Authentication\Http\Resources\UserResource;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Modules\Authentication\Http\Requests\UpdateProfileAvatar;
use Modules\Authentication\Http\Requests\UpdateProfileRequest;

class PersonalInfoController extends Controller
{
    use ApiResponseTrait;

    public function __construct(IUserRepository $user)
    {
        $this->user = $user;
    }



    public function updatepersonalProfile(UpdateProfileRequest $request)
    {
        $userProfile = $this->user->updateProfile($request);
        if($request->hasFile('avatar') && $request->file('avatar')->isValid()){
            $user = User::find(Auth::id());
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            $userProfile['data']=$user->fresh();
        }
        return $this->apiResponse(new UserResource($userProfile['data']), $userProfile['status'] , $userProfile['identifier_code'] , $userProfile['status_code'] , $userProfile['message']);


    }

    public function deleteAvatar(Request $request){

        $user = User::find(Auth::id());
        $user->clearMediaCollection('avatar');
        return $this->apiResponse( new UserResource($user),'true','115001',200,'Avatar deleted successfully' );
    }



    public function updateAvatar(UpdateProfileAvatar $request){

        $user = User::find(Auth::id());
            if ($request->hasFile('avatar')) {
                $user->clearMediaCollection('avatar');
                $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }

        return $this->apiResponse( new UserResource($user),'true','116001',200,'Avatar updated successfully' );
   }

   public function getuserInfo(Request $request){
    $user = User::find(Auth::id());
    return $this->apiResponse( new UserResource($user),'true','117001',200,'get user info successfully' );

   }



}
