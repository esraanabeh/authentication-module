<?php

namespace Modules\Authentication\Repositories\Interfaces;

interface IPasswordRepository{

    public function sendResetLinkEmail($data);
    public function resetPassword($data);
    

}









?>
