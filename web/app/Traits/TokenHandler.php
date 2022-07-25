<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 *
 */
trait TokenHandler
{
    public function createToken($username, $password)
    {
        $req = Request::create("/oauth/token", "POST", [
            'grant_type' => 'password',
            'client_id' => config('settings.token_params.id'),
            'client_secret' => config('settings.token_params.secret'),
            'username' => $username,
            'password' => $password,
            'scope' => '*'
        ]);

        $res = app()->handle($req);

        return json_decode($res->getContent());
    }

    public function refreshToken($token)
    {
        $req = Request::create("/oauth/token", "POST", [
            'grant_type' => 'refresh_token',
            'client_id' => config('settings.token_params.id'),
            'client_secret' => config('settings.token_params.secret', ''),
            'refresh_token' => $token,
            'scope' => '*'
        ]);

        $res = app()->handle($req);

        return json_decode($res->getContent());
    }
}
