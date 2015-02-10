<?php

namespace Kompas\LaravelKompasSso;


use Illuminate\Auth\Guard;
use Illuminate\Support\Facades\Session;

class KompasSsoGuard extends Guard {

    public function user()
    {
        $user = parent::user();
        if (is_null($user)) {
            return $this->user = $this->provider->retrieveByCredentials(array());
        }
        return $user;
    }

    public function logout()
    {
        $this->provider->destroy();

        Session::forget($this->provider->getTokenName());
        parent::logout();
    }

    public function getLoginUrl()
    {
        return $this->provider->getLoginUrl();
    }
}