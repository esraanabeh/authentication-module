<?php

namespace Modules\Authentication\Repositories\Repos;
use App\Models\User;
use Modules\SubscriptionPlan\Models\Subscription;
use Carbon\Carbon;
use Modules\Authentication\Models\Verification;
use Modules\Authentication\Repositories\Interfaces\IUserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Authentication\Http\Resources\UserResource;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Intervention\Image\Facades\Image as Image;
use Modules\Authentication\Jobs\SendAdminVerificarionMailJob;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Jobs\SendAdminChangePasswordMailJob;
use Modules\Organization\Models\Organization;

class UserRepository implements IUserRepository{

    use ApiResponseTrait;

    public function register($request)
    {
        $email=User::whereEmail($request->email)->first();

        if($email)
        {
            $user_id=$email->id;
            if ($email->is_verified==1)
            {
                return [
                    'data'            => new UserResource($email),
                    'status'          => false,
                    'identifier_code' => 100002,
                    'status_code'     => 400,
                    'message'         => 'this email already registered and verified before '
                ];
            }
            else
            {
                // get verification code data
                $data=verification::where('user_id', $user_id)->latest()->first();
                if($data)
                {
                    $expiration_time=(Carbon::now()->diffInHours($data->created_at)<=24);
                    if ($email->is_verified==0  && $expiration_time) {
                        return [
                            'data'            => new UserResource($email),
                            'status'          => true,
                            'identifier_code' => 100006,
                            'status_code'     => 200,
                            'message'         => 'this email already registered but not verified,please verify your email and login'
                        ];
                    }

                    if ($email->is_verified==0  && !$expiration_time){
                    // fire send verifivation mail job
                        dispatch(new SendAdminVerificarionMailJob($email,$this));
                        return [
                            'data'            => new UserResource($email),
                            'status'          => true,
                            'identifier_code' => 100004,
                            'status_code'     => 200,
                            'message'         => 'this email already registered but not verified,check mail with your new code'
                        ];
                    }
                }
                else
                {
                    // fire send verifivation mail job
                    dispatch(new SendAdminVerificarionMailJob($email,$this));
                    return [
                        'data'            => new UserResource($email),
                        'status'          => false,
                        'identifier_code' => 100005,
                        'status_code'     => 400,
                        'message'         => 'this email already registered but not verified,check mail to get the verification code'
                    ];
                }
            }
        }
        else
        {
            $user= $this->createUser($request);
            // fire send verifivation mail job
            event(new Registered($user));
            dispatch(new SendAdminVerificarionMailJob($user,$this));
            return [
                'data'            => new UserResource($user->fresh()),
                'status'          => true,
                'identifier_code' => 100001,
                'status_code'     => 200,
                'message'         => 'account created successfully, plz check your mail to get account verification pin code'
            ];
        }
    }

    public function createUser($request){

        return User::create([
            'first_name' => $request->post( 'first_name' ),
            'last_name' => $request->post( 'last_name' ),
            'email' => $request->post( 'email' ),
            'password' => Hash::make( $request->post( 'password' ) ),
            'role' => "admin",
        ]);

    }


    public function createCode($userId,$type='register'){

        // check if there is code belong to the logged in user
        $data=Verification::where('user_id',$userId)
                            ->where('type',$type)
                            ->latest()
                            ->first();
        // in case data exist
        if($data)
        {
            // check pin code is expired or used
            // if not
            if(Carbon::now()->diffInHours($data->created_at)<=24 && $data->is_used==0)
            {
                 return $data;
            }
            // if expired or used=> generate new one
            else
            {
                $code=rand(100000, 999999);
                return Verification::create( [
                    'user_id'  => $userId,
                    'pin_code' => $code,
                    'type'     => $type,

                ] );
            }
        }
        // in case no pin code => generate new one
        else
        {
            $code=rand(100000, 999999);
            return Verification::create( [
                'user_id'  => $userId,
                'pin_code' => $code,
                'type'     => $type,
            ] );
        }

    }

    public function mailVerification($request){
        $user = User::whereEmail($request->email)->first();

        if(is_null($user))
        {
            return [
                'data'            => NULL,
                'status'          => false,
                'identifier_code' => 104005,
                'status_code'     => 400,
                'message'         => 'email not exist, plz provide a registered mail'
            ];
        }
        else
        {
            $verify_user = Verification::whereHas('user',function($query) use($request) {
                                    $query->whereEmail($request->email);
                                })
                                ->where("pin_code",$request->post('pin_code'))
                                ->where("type","register")
                                ->first();
            if($verify_user)
            {
                // if pin code expired
                if(!(Carbon::now()->diffInHours($verify_user->created_at)<=24))
                {
                    $user = User::where('email' ,$request->email)->first();
                    dispatch(new SendAdminVerificarionMailJob($user,$this));
                    return [
                        'data'            => NULL,
                        'status'          => false,
                        'identifier_code' => 104004,
                        'status_code'     => 400,
                        'message'         => 'Pin code has been expired please check mail for new one'
                    ];


                }
                if($verify_user->is_used==1)
                {
                    return [
                        'data'            => NULL,
                        'status'          => false,
                        'identifier_code' => 104002,
                        'status_code'     => 400,
                        'message'         => 'Pin code used before'
                    ];
                }
                else
                {
                    $token =  $user->createToken('API Token');
                    $verify= $this->verify($request->post('email'),$request->post('pin_code'));
                    if($verify)
                    {
                        return [

                                'data' => [
                                    'user' =>  new UserResource($user),
                                    'access_token' => $token->plainTextToken
                                    ],
                            'status'          => true,
                            'identifier_code' => 104001,
                            'status_code'     => 200,
                            'message'         => 'Account verified successfully'
                        ];
                    }
                    else
                    {
                        return [
                            'data'            => NULL,
                            'status'          => false,
                            'identifier_code' => 104003,
                            'status_code'     => 400,
                            'message'         => 'Some thing went wrong, plz try again'
                        ];
                    }
                }
            }
            else
            {
                return [
                    'data'            => NULL,
                    'status'          => false,
                    'identifier_code' => 104004,
                    'status_code'     => 400,
                    'message'         => 'Incorrect pin code'
                ];
            }

        }

    }
    public function verify($email,$code){
            $data= Verification::whereHas('user',function($query) use($email) {
                $query->whereEmail($email);
            })->where("type","register")->latest()->first();
            if($data)
            {
                if($data->pin_code==$code )
                {
                    $user=User::find($data->user_id);
                    $user->update(['email_verified_at'=>Carbon::now(),'is_verified'=>1]);
                    $data->update(['is_used'=>1]);
                    return true;
                }

                return false;
            }
    }

    public function login($data)
    {
        $user = Auth::attempt(['email' => $data['email'], 'password' => $data['password']]);
        if($user)
        {
            // check mail verification status
            $token  =  auth()->user()->createToken('API Token');
            // check user status
            if(auth()->user()->status==0)
            {
                return [
                    'data' => [
                        'user' => new UserResource(auth()->user()),
                        'access_token' => $token->plainTextToken
                    ],
                    'status' => false,
                    'identifier_code' => 101006,
                    'status_code' => 400,
                    'message' => 'Your account is suspended, for more details plz contact support'
                ];
            }

            // check mail verification
            if(auth()->user()->is_verified==0)
            {
                $result = Verification::where('user_id',auth()->user()->id)->latest()->first();
                // in case verification mail sent
                if($result)
                {
                    $expiration_time=(Carbon::now()->diffInHours($result->created_at)<=24);
                    if(!$expiration_time && $result->is_used==0) {
                        dispatch(new SendAdminVerificarionMailJob(auth()->user(),$this));
                    }
                }
                else
                {
                    dispatch(new SendAdminVerificarionMailJob(auth()->user(),$this));
                }
                return [
                    'data' => [
                        'user' => new UserResource(auth()->user()),
                        'access_token' => $token->plainTextToken
                    ],
                    'status' => false,
                    'identifier_code' => 101005,
                    'status_code' => 400,
                    'message' => 'Your account is not verified, plz verify your account first'
                ];
            }

           $userID = Auth::user()->id;
           if(auth()->user()->allow_2fa){
                $qr_code = $this->generate2FAcode(auth()->user());
                return [
                    'data' => [
                        'email'   => auth()->user()->email,
                        'qr_code' => $qr_code,
                        ],
                    'status'          => true,
                    'identifier_code' => 101002,
                    'status_code'     => 200,
                    'message'         => '2fa authentication is required'
                ];
           }

           else
           {
            return [
                'data' => [
                    'user'         => new UserResource(auth()->user()),
                    'access_token' => $token->plainTextToken,
                    ],
                'status'          => true,
                'identifier_code' => 101001,
                'status_code'     => 200,
                'message'         => 'user logged in successfully'
                ];

           }

        }
        else
        {
            return [
            'data' => null,
            'status' => false,
            'identifier_code' => 101003,
            'status_code' => 400,
            'message' => 'invalid email or password'
            ];
        }
    }

    public function generate2FAcode($user)
    {
        $google2fa = app('pragmarx.google2fa');
        $code = $google2fa->generateSecretKey();

        User::whereId($user->id)->update(['google2fa_secret' => $code]);

        $QR_Image = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $code
        );

        return $QR_Image;
    }

    public function verify2FAstep($data)
    {
        $user = User::where('email' , $data['email'])->first();

        $google2fa = app('pragmarx.google2fa');

        $valid = $google2fa->verifyKey($user->google2fa_secret, $data['google2fa_secret']);

        if (!$valid) {
            return [
                'data' => new UserResource($user),
                'status' => false,
                'identifier_code' => 102002,
                'status_code' => 400,
                'message' => 'one-time password is invalid'
            ];
        }
        $token =  $user->createToken('API Token');
        return [
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token->plainTextToken
                ],
            'status' => true,
            'identifier_code' => 102001,
            'status_code' => 200,
            'message' => 'user logged in successfully'
        ];
    }


    public function createPasswordCode($userId,$code){
        return Verification::create( [
            'user_id' => $userId,
            'pin_code' => $code,
            'type'=>'reset_password'
        ] );

    }


    public function resetCode($email,$code){
        $data= Verification::whereHas('user',function($query) use($email) {
            $query->whereEmail($email);
        })->where('type','reset_password')->first();
        // dd($code);
        if($data){
            // dd($data);
            if($data->pin_code==$code){
                $user=User::find($data->user_id);
                $data->update(['is_used'=>1]);
                return true;
            }
            return false;
        }

        }

    public function updatePassword($oldPassword,$newPassword){
        $user = Auth::user();
        if(!Hash::check($oldPassword , $user->password))
        {
            return [
                'data' => new UserResource(auth()->user()),
                'status' => false,
                'identifier_code' => 106002,
                'status_code' => 400,
                'message' => 'Account password is incorrect'
            ];
        }
        else

        {
            //check 2fa
            if($user->allow_2fa==0)
            {
                try
                {
                    $user->update(['password' => Hash::make($newPassword)]);
                    return [
                        'data'            => new UserResource(auth()->user()),
                        'status'          => true,
                        'identifier_code' => 106001,
                        'status_code'     => 200,
                        'message'         => 'Account password updated successfully'
                    ];
                }
                catch (\Exception $ex)
                {
                    Log::info("Password change process failed due to : ".$ex->getMessage());
                    return [
                        'data'            => new UserResource(auth()->user()),
                        'status'          => false,
                        'identifier_code' => 106003,
                        'status_code'     => 400,
                        'message'         => 'Some thing went wrong, plz try again later'
                    ];
                }


            }
            else
            {
                Auth::user()->update(['temp_password' => Hash::make($newPassword)]);
                dispatch(new SendAdminChangePasswordMailJob(Auth::user(),$this));
                return [
                    'data'            => new UserResource(auth()->user()),
                    'status'          => true,
                    'identifier_code' => 106004,
                    'status_code'     => 200,
                    'message'         => 'Pin code has been sent to your mail, please check your inbox'
                ];
            }
        }
    }

    public function confirmChangePassword($request){

            $checkPinCode = Verification::where("pin_code",$request->post('pin_code'))
                                ->where("user_id",Auth::user()->id)
                                ->where("type","change_password")
                                ->first();
            if($checkPinCode)
            {
                // if pin code expired
                if(! (Carbon::now()->diffInHours($checkPinCode->created_at)<=24))
                {
                    dispatch(new SendAdminChangePasswordMailJob(Auth::user(),$this));
                    return [
                        'data'            => NULL,
                        'status'          => false,
                        'identifier_code' => 124004,
                        'status_code'     => 400,
                        'message'         => 'Pin code has been expired please check mail for new one'
                    ];


                }
                if($checkPinCode->is_used==1)
                {
                    return [
                        'data'            => NULL,
                        'status'          => false,
                        'identifier_code' => 124003,
                        'status_code'     => 400,
                        'message'         => 'Pin code used before'
                    ];
                }
                else
                {
                    Auth::user()->update(['password' => Auth::user()->temp_password]);
                    $checkPinCode->is_used=1;
                    $checkPinCode->save();

                        return [
                            'data'            => NULL,
                            'status'          => true,
                            'identifier_code' => 124001,
                            'status_code'     => 200,
                            'message'         => 'Password updated successfully'
                        ];


                }
            }
            else
            {
                return [
                    'data'            => NULL,
                    'status'          => false,
                    'identifier_code' => 124002,
                    'status_code'     => 400,
                    'message'         => 'Incorrect pin code'
                ];
            }


    }

    public function update2FAStatus($data)
    {
        $google2fa = app('pragmarx.google2fa');
        $user = User::where('email' , $data['email'])->first();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $data['google2fa_secret']);
        if (!$valid) {
            return [
                'data' => new UserResource(auth()->user()),
                'status' => false,
                'identifier_code' => 123002,
                'status_code' => 400,
                'message' => 'one-time password is invalid'
            ];
        }
        $user->update(['allow_2fa' => !$user->allow_2fa]);
        return [
            'data' => new UserResource($user->refresh()),
            'status' => true,
            'identifier_code' => 123001,
            'status_code' => 200,
            'message' => '2FA Status Updated Successfully'
        ];
    }



     public function updateProfile($request)
     {
        try
        {
            // begin transaction
            DB::beginTransaction();

            $user = User::find(Auth::id());
            $email = $user->email;
            $user->update([
                'first_name' => $request->post('first_name'),
                'last_name' => $request->post('last_name'),
                'phone_number' => $request->post('phone_number'),
                'email' => $request->post('email'),

            ]);

            // send verification mail
            if($email != $request->post('email')){
                $user->update(['is_verified' => 0]);
                dispatch(new SendAdminVerificarionMailJob($user->fresh(),$this));
            }


            // sync user data with stripe
            if(!is_null($user->stripe_id))
            {
                $options =[
                    'name'         => $request->post('first_name')." ".$request->post('last_name'),
                    'phone'        => $request->post('phone_number'),
                    'email'        => $request->post('email'),
                ];
            $user->updateStripeCustomer($options);
            }

            // Happy ending :)
            DB::commit();
            return [
                'data'            => $user->fresh(),
                'status'          => true,
                'identifier_code' => 113001,
                'status_code'     => 200,
                'message'         => 'personal profile updated successfully'
            ];
        }
        catch (\Exception $e) {
            // rollback!!!
            DB::rollback();
            Log::info("update user profile issue : ".$e->getMessage());
            return [
                'data'            => NULL,
                'status'          => false,
                'identifier_code' => 113004,
                'status_code'     => 400,
                'message'         => 'Some thing went wrong, please try again later'
            ];
        }
     }



    public function updateuserAvatar($request)
    {

         $avatar = Media::where('model_id',Auth::id());

          return $avatar;
    }


}








?>
