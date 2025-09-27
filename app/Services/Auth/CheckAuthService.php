<?php

namespace App\Services\Auth;

class CheckAuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function checkAuth()
    {
        if(session()->get('user_id') && session()->get('auth_token') && session()->get('authenticated')){
            return true;
        }
        return false;
    }
}
