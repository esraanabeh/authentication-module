<?php

namespace Modules\Authentication\Repositories\Interfaces;

interface IUserRepository{
    public function createUser($data);
    public function createCode($userId );
    public function verify($email , $code);
    public function createPasswordCode($userId , $code);
    public function resetCode($email , $code);
    public function updatePassword($oldPassword,$newPassword);
    public function update2FAStatus($data);
    public function updateProfile($request);



}









?>
