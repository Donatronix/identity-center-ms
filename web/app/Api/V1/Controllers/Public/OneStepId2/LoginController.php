<?php

namespace App\Api\V1\Controllers\Public\OneStepId2;

use App\Api\V1\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\VerifyStepInfo;
use App\Services\SendVerifyToken;

class LoginController extends Controller
{
    /**
     * Login user endpoint
     *
     * @OA\Post(
     *     path="/user-account/v2/login",
     *     description="User login for One-Step 2.0",
     *     tags={"OneStep 2.0 | User Account Login"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="Username",
     *                 required={"true"},
     *                 example="john.kiels"
     *             ),
     *             @OA\Property(
     *                 property="channel",
     *                 type="string",
     *                 description="OTP type or channel (SMS or Messenger).",
     *                 required={"true"},
     *                  example="sms"
     *             ),
     *             @OA\Property(
     *                 property="handler",
     *                 type="string",
     *                 description="Account verification handler.",
     *                 required={"true"},
     *                 example="@ultainfinity"
     *             ),
     *             @OA\Property(
     *                 property="isAdmin",
     *                 type="boolean",
     *                 description="Login as Admin",
     *                 required={"false"},
     *                 example="false"
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
     *                 property="title",
     *                 type="string",
     *                 example="User login"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User login successful"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     example="john.kiels"
     *                 ),
     *                 @OA\Property(
     *                     property="otp",
     *                     type="string",
     *                     example="4232590"
     *                 )
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
     *                 property="title",
     *                 type="string",
     *                 example="User login"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User login failed"
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
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'channel' => 'required|string',
                'handler' => 'required|string',
                'username' => 'required|string',
                'isAdmin' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->jsonApi([
                    'title' => "User login",
                    'message' => "Input validator errors. Try again.",
                    'data' => $validator->errors()
                ], 422);
            }

            // Get validated input
            $input = $validator->validated();
            if (strpos($input['username'], '@')) {
                $input['username'] = explode('@', $input['username'])[0];
            }

            //Get user query
            $userQuery = User::where('username', $input['username']);

            if($userQuery->exists()) {
                // Get user
                $user = $userQuery->first();

                // Login As
                if(isset($input['isAdmin']) && $input['isAdmin']) {
                    if (!$user->hasRole('Admin') || !$user->hasRole('Super')) {
                        return response()->jsonApi([
                            'title' => 'Login',
                            'message' => "Permission denied",
                        ], 400);
                    }
                }

                // Create verification token (OTP)
                $otpToken = VerifyStepInfo::generateOTP(6);
                $validity = VerifyStepInfo::tokenValidity(30);

                $send_to = $user->phone;
                if ($input['channel'] != 'sms') {
                    $send_to = $input['handler'];
                }

                VerifyStepInfo::create([
                    'username' => $input['username'],
                    'channel' => $input['channel'],
                    'receiver' => $send_to,
                    'code' => $otpToken,
                    'validity' => $validity
                ]);

                $sendOTP = new SendVerifyToken();
                $sendOTP->dispatchOTP($input['channel'], $send_to, $otpToken);

                // For Testing purpose
//                if (app()->environment('local', 'staging')) {
//                    $data['otp'] = $otpToken;
//                }

                //Send response
                return response()->jsonApi([
                    'title' => 'User login',
                    'message' => "{$input['channel']} verification code sent to {$send_to}.",
                   // 'data' => $data
                ]);
            }

            //Show response
            return response()->jsonApi([
                'title' => 'User login',
                'message' => 'User does NOT exist. Try again',
            ], 400);
        }catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'User login',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * User login verification for OneStep2.0
     *
     * @OA\Post(
     *     path="/user-account/v2/login/verify-otp",
     *     description="Verify user login",
     *     tags={"OneStep 2.0 | User Account Login"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="Username",
     *                 required={"true"},
     *                 example="john.kiels"
     *             ),
     *             @OA\Property(
     *                 property="login_otp",
     *                 type="string",
     *                 description="User login OTP",
     *                 required={"true"},
     *                 example="9284756"
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
     *                 property="title",
     *                 type="string",
     *                 example="Verify user login"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User login verification successful"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     example="john.kiels"
     *                 ),
     *                 @OA\Property(
     *                     property="access_token",
     *                     type="string",
     *                     example="jhjdhd9JJHJjh96klnvv878lLH7G34Jjh98"
     *                 )
     *
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
     *                 property="title",
     *                 type="string",
     *                 example="Verify user login"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User login verification failed"
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
     * @return Response
     */
    public function verifyOTP(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'login_otp' => 'required|string',
                'username' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->jsonApi([
                    'title' => "Verify user login",
                    'message' => "Input validator errors. Try again.",
                    'data' => $validator->errors()
                ], 422);
            }

            //Get validated input
            $input = $validator->validated();

            //get user query
            $userQuery = VerifyStepInfo::where([
                        'code' => $input['login_otp'],
                        'username'=> $input['username']
                    ]);

            if ($userQuery->exists()) {
                //Get user
                $user = User::where('username', $input['username'])->first();

                //Create user access token
                $data['token'] = $user->createToken($input['username'])->accessToken;
                $data['user'] = $this->prepRole($user);

                //Delete login OTP
                $userQuery->delete();

                return response()->jsonApi([
                    'type' => 'success',
                    'title' => 'Verify user login',
                    'message' => "User login was successfull",
                    'data' => $data
                ]);
            }

            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Verify user login',
                'message' => "Invalid login verification code.",
                'data' => null
            ], 400);
        }
        catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'Verify user login',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Refresh expired Token
     *
     * @OA\Post(
     *     path="/user-account/v2/login/refresh-token",
     *     summary="Refresh Token",
     *     description="Refresh expired Token",
     *     tags={"OneStep 2.0 | User Account Login"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Refresh Token",
     *                 example="def502009171ac97fa3d2487..."
     *             )
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
     *                 property="title",
     *                 type="string",
     *                 example="Refresh token"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Token has been refreshed"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Token Object",
     *                 example="def50G5T87NxoJGH7fa3d2487"
     *             )
     *         )
     *     ),
     *
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
     *                 example="damger"
     *             ),
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 example="Refresh token"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Token refresh FAILED"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Token Object",
     *                 example=""
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            // Validate input data
            $validator = Validator::make($request->all(), [
                'token' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->jsonApi([
                    'title' => "Refresh token",
                    'message' => "Input validator errors. Try again.",
                    'data' => $validator->errors()
                ], 422);
            }

            //Get validated input
            $input = $validator->validated();

            //$token = $this->refreshToken($request);

            return response()->jsonApi([
                'title' => "Refresh token",
                "message" => "Token has been resfreshed successfully",
                "data" => null
            ]);

        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => "Refresh token",
                "message" => $e->getMessage(),
            ], 400);
        }
    }

    private function prepRole($user)
    {
        $role = $user->getRoleNames()[0];
        $user = $user->toArray();
        unset($user['roles']);
        $user['role'] = $role;
        return $user;
    }
}
