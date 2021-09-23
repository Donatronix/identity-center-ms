<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PassportAuthController extends Controller
{
    /**
     * handle user registration request
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'username' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $access_token_example = $user->createToken('PassportExample@Section.io')->access_token;

        //return the access token we generated in the above step
        return response()->json(['token' => $access_token_example], 200);
    }

    /**
     * login user to our application
     */
    public function login(Request $request)
    {
        $login_credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (auth()->attempt($login_credentials)) {
            //generate the token for the user
            $user_login_token = auth()->user()->createToken('PassportExample@Section.io')->accessToken;

            //now return this token on success login attempt
            return response()->json([
                'token' => $user_login_token
            ], 200);
        } else {
            //wrong login credentials, return, user not authorised to our system, return error code 401
            return response()->json([
                'error' => 'UnAuthorised Access'
            ], 401);
        }
    }

    /**
     * This method returns authenticated user details
     */
    public function user()
    {
        return response()->json(['authenticated-user' => auth()->user()], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ], 201);
    }
}
