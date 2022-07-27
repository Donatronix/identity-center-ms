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
     * Validation of the request parameter username.
     * Checking username if user with that username.
     * Checking if the username is empty.
     * Updating user with added username.
     * User authentication returns a token if he is logged in.
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
     *                 description="User's username",
     *                 example="chinedu338"
     *             ),
     *             @OA\Property(
     *                 property="sid",
     *                 type="string",
     *                 description="The Message SID is the unique ID for any message successfully created by One Step’s API",
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
                'sid' => 'required|string|min:8|max:36',
            ]);
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => "Validation error: " . $e->getMessage(),
                'data' => $e->validator->errors()
            ], 422);
        }

        // try to retrieve user using sid
        try {
            $authUser = TwoFactorAuth::where('sid', $request->get('sid'))
                ->with('user')
                ->orderBy('id', 'desc')
                ->firstOrFail()
                ->user;

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => 'SID not found or incorrect'
            ], 403);
        }


        // Check if user status is banned
        if ($authUser->status == User::STATUS_BANNED) {
            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => 'User has been banned from this platform',
//                'message' => 'You cannot use this service. Go to https://onestepid.com for identification and get OneStep ID.',
                'data' => [
                    "user_status" => $authUser->status,
                ]
            ], 403);
        }

        // Check if user status is inactive
        if ($authUser->status == User::STATUS_INACTIVE) {
            // Check if exist user for given username
            $existUser = User::where('username', $request->get('username'))->first();

            // If exist user found and exist user another then auth user, so
            if ($existUser && ($existUser->id !== $authUser->id)) {
                // username already exists for this SID
                return response()->jsonApi([
                    'title' => 'User authorization',
                    "message" => 'Username already exists',
                    'data' => [
                        'user_status' => $authUser->status,
                    ]
                ], 403);
            }

            // If inactive haven't username
            if (empty($authUser->username)) {
                // Finish user registration
                try {
                    // Update username and status
                    $authUser->username = $request->get('username', null);
                    $authUser->status = User::STATUS_ACTIVE;
                    $authUser->save();

                    // Join new user to referral program
                    PubSub::publish('NewUserRegistered', [
                        'user' => $authUser->toArray(),
                    ], config('pubsub.queue.referrals'));

                    // Subscribing new user to Subscription service
                    PubSub::publish('NewUserRegistered', [
                        'user' => $authUser->toArray(),
                    ], config('pubsub.queue.subscriptions'));

                    //Add Client Role to User
                    $role = Role::firstOrCreate([
                        'name' => USER::INVESTOR_USER
                    ]);
                    $authUser->roles()->sync($role->id);

                    // Do login, create access token and return
                    return $this->login($authUser, $request->all(), [
                        'success' => 'User was successfully activated',
                        'incorrect' => 'Username was added, but unable to create access token'
                    ]);
                } catch (Exception $e) {
                    return response()->jsonApi([
                        'title' => 'User authorization',
                        'message' => "Unable to set username: " . $e->getMessage(),
                        'data' => [
                            'user_status' => $authUser->status,
                            'phone_exist' => false
                        ]
                    ], 403);
                }
            } else {
                // if username is correct, means that user is disable
                if($authUser->username === $request->get('username')){
                    return response()->jsonApi([
                        'title' => 'User authorization',
                        'message' => "User is inactive. You can't use this service",
                        'data' => [
                            'user_status' => $authUser->status,
                            'phone_exist' => false
                        ]
                    ], 403);
                }else{
                    return response()->jsonApi([
                        'title' => 'User authorization',
                        "message" => 'Incorrect username for this account'
                    ], 403);
                }
            }
        }

        // if user is active and username is correct, do login
        if ($authUser->status == User::STATUS_ACTIVE) {
            if($authUser->username === $request->get('username')){
                return $this->login($authUser, $request->all(), [
                    'success' => 'User logged in successfully',
                    'incorrect' => 'Authorisation Error. Unable to create access token'
                ]);
            }else{
                return response()->jsonApi([
                    'title' => 'User authorization',
                    'message' => 'Incorrect username for this account'
                ], 403);
            }
        }
    }

    /**
     * @param User $user
     * @param array $input
     * @param array $messages
     * @return JsonResponse
     */
    private function login(User $user, array $input, array $messages): JsonResponse
    {
        // Check if its a malicious user
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
//            if (strtolower($user->username) !== strtolower($input['username'])) {
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

            // Generate access token
            $token = $user->createToken($user->username)->accessToken;

            // Deleting SID
            TwoFactorAuth::where('sid', $input['sid'])->delete();

//            $redis->del($userLoginAttemptsKey);

            $user = collect($user->toArray());

            // Return response
            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => $messages['success'],
                'data' => [
                    'token' => $token,
                    'user' => $user->only([
                        'id',
                        'first_name',
                        'last_name',
                        'display_name',
                        'username',
                        'gender',
                        'birthday',
                        'phone',
                        'email',
                        'avatar',
                        'locale',
                        'address_zip',
                        'address_country',
                        'address_city',
                        'address_line1',
                        'address_line2',
                        'status'
                    ])
                ]
            ]);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'User authorization',
                'message' => $messages['incorrect'] . ': ' . $e->getMessage(),
            ], 403);
        }
    }
}
