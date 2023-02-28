<?php

use Illuminate\Http\Request;
use Modules\Authentication\Http\Controllers\API\AuthenticationController;
use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\API\ForgetPasswordController;
use Modules\Authentication\Http\Controllers\API\ChangePasswordController;
use Modules\Authentication\Http\Controllers\API\PersonalInfoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//organization register
Route::group(['prefix' => 'auth'],function ($router){
    Route::post('/register'        , [AuthenticationController::class , 'register']);
    Route::post('/verify'          , [AuthenticationController::class , 'verifyCode']);
    Route::post('/login'           , [AuthenticationController::class , 'login'])->name('user.login');
    Route::post('/2fa'             , [AuthenticationController::class , 'completeLogin'])->name('user.login.2fa');
    Route::post('/forget-password' , [ForgetPasswordController::class , 'forgetPassword'])->name('user.forget-password');
    Route::post('/reset-password'  , [ForgetPasswordController::class , 'resetPassword'])->name('user.password.reset');
    Route::post('/resend-pin-code' ,[AuthenticationController::class  , 'resendPinCode'])->name('user.resend.pin-code');
});


Route::group(['middleware' => ['auth:sanctum']], function ($router) {
    Route::get('logout'                   , [AuthenticationController::class , 'logout'])->name('user.logout');
    Route::get('get_user_info'            , [PersonalInfoController::class   , 'getuserInfo']);
    Route::post('update_personal_profile' , [PersonalInfoController::class   , 'updatepersonalProfile']);

});


