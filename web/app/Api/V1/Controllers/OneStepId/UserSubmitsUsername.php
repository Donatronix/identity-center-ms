<?php

namespace App\Api\V1\Controllers\OneStepId;

use App\Api\V1\Controllers\Controller;
use App\Models\TwoFactorAuth;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;
use PubSub;

class UserSubmitsUsername extends Controller
{
    const MAX_LOGIN_ATTEMPTS = 3;
    const LOGIN_ATTEMPTS_DURATION = 120; //secs

    /**
     * User Submits Account Username
     *
     * @OA\Post(
     *     path="/auth/send-username",
     *     summary="User Submits Account Username",
     *     description="Here the new user or the existing user submits username for login, along with the sid",
     *     tags={"Auth by OneStep"},
     *
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
     *          response=200,
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
     *          response=400,
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
        // Validate input data
        $this->validate($request, [
            'username' => 'required',
            "sid" => "required",
        ]);

        // try to retrieve user using sid
        try {
            // retrieve user using the sid
            $user = User::getBySid($request->sid);
        } catch (ModelNotFoundException $th) {
            return response()->json([
                "type" => "danger",
                "message" => "Invalid sid Token",
            ], 403);
        }

        // check user status
        if ($user->status == User::STATUS_BANNED) {
            //report banned
            return response()->json([
                "type" => "danger",
                "user_status" => $user->status,
                "message" => "User has been banned from this platform.",
            ], 403);
        } elseif ($user->status == User::STATUS_ACTIVE) {
            //login active user
            return $this->login($user, $request->sid, $request->username);
        }

        // Only  inactive users gets to this part of the code
        // check if username is taken
        $usernameExists = User::where("username", $request->username)->exists();
        if ($usernameExists) {
            return response()->json([
                "type" => "danger",
                "message" => "Username already exists.",
                "user_status" => $user->status,
                "phone_exist" => true,
            ], 400);
        }

        // check if username is empty
        if (empty($user->username)) {
            try {
                $user->username = $request->username;
                $user->status = User::STATUS_ACTIVE;
                $user->save();

                PubSub::transaction(function () {
                })->publish('NewUserRegisteredListener', [
                    'user' => $user->toArray(),
                ], 'new-user-registered');

            } catch (Exception $th) {
                //throw $th;
                return response()->json([
                    "type" => "danger",
                    "message" => "Unable to save username.",
                ], 400);
            }

            return $this->login($user, $request->sid, $request->username);
        } else {
            // username already exists for this SID
            return response()->json([
                "type" => "danger",
                "message" => "Username already exists for this SID",
            ]);
        }
    }

    /**
     * @param User             $user
     * @param                  $sid
     * @param                  $username
     *
     * @return JsonResponse
     */
    private function login(User $user, $sid, $username): JsonResponse
    {
        //check if its a malicious user
        try {
            $user = User::getBySid($sid);
            $redis = Redis::connection();

            $userLoginAttemptsKey = "login_attempts:" . $user->id;

            if (!$redis->exists($userLoginAttemptsKey)) {
                //set the key
                $redis->set($userLoginAttemptsKey, 1);
                //set the expiration
                //I understand this means expire in 120s.
                $redis->expire($userLoginAttemptsKey, self::LOGIN_ATTEMPTS_DURATION);

            } else {
                $count = 0;
                $count += (int)$redis->get($userLoginAttemptsKey);
                $redis->set($userLoginAttemptsKey, $count);
            }

            if (strtolower($user->username) !== strtolower($username)) {
                $loginAttempts = (int)$redis->get($userLoginAttemptsKey);

                if ($loginAttempts > self::MAX_LOGIN_ATTEMPTS - 1)

                    // malicious user, warn and block
                    //TODO count login attempts and block
                    return response()->json([
                        "type" => "danger",
                        "message" => "Unauthorized operation.",
                        "user_status" => $user->status,
                    ], 403);
            }
            // generate access token
            $token = $user->createToken("bearer")->accessToken;
            // delete sid

            $twoFa = TwoFactorAuth::where("sid", $sid)->first();
            $twoFa->delete();

            $redis->del($userLoginAttemptsKey);

            return response()->json([
                "message" => "Login successful",
                "type" => "success",
                "token" => $token,
            ]);

        } catch (Exception $e) {
            return response()->json([
                "type" => "danger",
                "message" => "Invalid SID",
            ], 403);
        }
    }
}
