<?php

namespace App\Api\V1\Controllers\Public\OneStepId1;

use App\Api\V1\Controllers\Controller;
use App\Models\TwoFactorAuth;
use App\Models\User;
use App\Traits\TokenHandler;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PubSub;

//use Illuminate\Support\Facades\Redis;

class UsernameSubmitController extends Controller
{
    use TokenHandler;

    const MAX_LOGIN_ATTEMPTS = 3;
    const LOGIN_ATTEMPTS_DURATION = 120; //secs

    /**
     * Submit username account
     *
     * @OA\Post(
     *     path="/user-account/v1/send-username",
     *     summary="Submit username account",
     *     description="Here the new user or the existing user submits username for login, along with the sid",
     *     tags={"OneStep 1.0"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"username"},
     *
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="verification code enter by user",
     *                 example="chinedu338"
     *             ),
     *             @OA\Property(
     *                 property="sid",
     *                 type="string",
     *                 description="Message ID",
     *                 example="dawsd-sdsd-sdfsds-dsd"
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="sid",
     *                 type="string",
     *                 example="Create ew user. Step 3"
     *             ),
     *             @OA\Property(
     *                 property="validate_auth_code",
     *                 type="boolean",
     *                 example="true",
     *                 description="Indicates if validation is successful"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User was successful created"
     *             ),
     *             @OA\Property(
     *                 property="user_status",
     *                 type="number",
     *                 description="User Status INACTIVE = 0, ACTIVE = 1, BANNED = 2"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *
     *          @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="danger"
     *             ),
     *             @OA\Property(
     *                 property="validate_auth_code",
     *                 type="boolean",
     *                 example="false"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example=""
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *                 example=""
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Validate input data
            $this->validate($request, [
                'username' => 'required|string|min:3',
                'sid' => 'required|string|min:8',
            ]);
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => "Validation error: " . $e->getMessage(),
                'data' => $e->validator->errors()->first()
            ], 422);
        }

        // try to retrieve user using sid
        try {
            $twoFa = TwoFactorAuth::where("sid", $request->get('sid'))
                ->with('user')
                ->firstOrFail();

            $user = $twoFa->user;
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => 'SID not found or incorrect',
            ], 403);
        }

        // check user status
        if ($user->status == User::STATUS_BANNED) {
            //report banned
            return response()->jsonApi([
                'title' => 'User authorization',
                "user_status" => $user->status,
                "message" => "User has been banned from this platform.",
            ], 403);
        }

        // Only  inactive users gets to this part of the code
        // check if username is taken
//        $usernameExists = User::where("username", $request->get('username'))->exists();
//        if ($usernameExists) {
//            return response()->jsonApi([
//                'title' => 'User authorization',
//                "message" => "Username already exists.",
//                "user_status" => $user->status,
//                "phone_exist" => true,
//            ], 400);
//        }

        // check if username is empty, Do finish register user
        if (empty($user->username)) {
            try {
                $user->username = $request->get('username');
                $user->status = User::STATUS_ACTIVE;
                $user->save();

                // Join new user to referral programm
                PubSub::publish('NewUserRegistered', [
                    'user' => $user->toArray(),
                ], config('pubsub.queue.referrals'));

                // Subscribing new user to Subscription service
                PubSub::publish('NewUserRegistered', [
                    'user' => $user->toArray(),
                ], config('pubsub.queue.subscriptions'));

                // Set role to user
                $user->assignRole('client');
            } catch (Exception $e) {
                return response()->jsonApi([
                    'title' => 'User authorization',
                    "message" => "Unable to save username: " . $e->getMessage(),
                ], 400);
            }
        } else {

//        if ($user->status == User::STATUS_ACTIVE) {
//            // Login active user
//            return $this->login($user, $request->get('sid'), $request->get('username'));
//        }

            // username already exists for this SID
//            return response()->jsonApi([
//                'title' => 'User authorization',
//                "message" => "Username already exists for this SID",
//            ]);
        }

        // Do login, create access token and return
        return $this->login($user, $request->get('sid'), $request->get('username'));
    }

    /**
     * @param User $user
     * @param                  $sid
     * @param                  $username
     *
     * @return JsonResponse
     */
    private function login(User $user, $sid, $username): JsonResponse
    {
        //check if its a malicious user
        try {
//            $redis = Redis::connection();
//
//            $userLoginAttemptsKey = "login_attempts:" . $user->id;
//
//            if (!$redis->exists($userLoginAttemptsKey)) {
//                //set the key
//                $redis->set($userLoginAttemptsKey, 1);
//                //set the expiration
//                //I understand this means expire in 120s.
//                $redis->expire($userLoginAttemptsKey, self::LOGIN_ATTEMPTS_DURATION);
//            } else {
//                $count = 0;
//                $count += (int)$redis->get($userLoginAttemptsKey);
//                $redis->set($userLoginAttemptsKey, $count);
//            }
//
//            if (strtolower($user->username) !== strtolower($username)) {
//                $loginAttempts = (int)$redis->get($userLoginAttemptsKey);
//
//                if ($loginAttempts > self::MAX_LOGIN_ATTEMPTS - 1) {
//                    // malicious user, warn and block
//                    //TODO count login attempts and block
//                    return response()->jsonApi([
//                        "message" => "Unauthorized operation.",
//                        "user_status" => $user->status,
//                    ], 403);
//                }
//            }

            //    dd($user);

            // Generate access token
            $token = $user->createToken($user->username)->accessToken;

            // delete sid
            $twoFa = TwoFactorAuth::where('sid', $sid)->first();
            $twoFa->delete();

//            $redis->del($userLoginAttemptsKey);

            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => "Login successful",
                'data' => [
                    'token' => $token,
                    'user' => $user
                ]
            ]);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
