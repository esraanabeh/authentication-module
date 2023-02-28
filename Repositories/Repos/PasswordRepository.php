<?php

namespace Modules\Authentication\Repositories\Repos;

use Modules\Authentication\Models\ResetPassword;
use Illuminate\Support\Facades\Password;
use Modules\Authentication\Repositories\Interfaces\IPasswordRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordRepository implements IPasswordRepository{

    public function broker()
    {
        return Password::broker('users');
    }

    public function sendResetLinkEmail($data)
    {
        $response = $this->broker()->sendResetLink(Arr::only($data,['email']));

        return $response == Password::RESET_LINK_SENT
            ? ['data' => null , 'status' => true , 'identifier_code' => 108001,'status_code' => 200,'message' => 'reset password link sent']
            : ['data' => null , 'status' => false , 'identifier_code' => 108002,'status_code' => 400,'message' => 'Something wrong'];
    }

    public function resetPassword($data)
    {
        try{
            $status = Password::reset(
                $data,
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );


            return $status === Password::PASSWORD_RESET
                ? ['data' => null , 'status' => true , 'identifier_code' => 109001,'status_code' => 200,'message' => 'Your password has been reset successfully']
                : ['data' => null , 'status' => false , 'identifier_code' => 109002,'status_code' => 400,'message' => 'Something wrong'];

        }catch (\Exception $e){
           
            return [
                'data' =>null,
                'status' => false,
                'identifier_code' => 109003,
                'status_code' => 400,
                'message' => 'Invalid token, You can request another reset password link again'
            ];
        }

    }

}

?>
