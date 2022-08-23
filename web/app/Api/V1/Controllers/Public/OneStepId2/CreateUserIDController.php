<?php

namespace App\Api\V1\Controllers\Public\OneStepId2;

use App\Api\V1\Controllers\Controller;
use App\Models\RecoveryQuestion;
use App\Models\User;
use App\Models\VerifyStepInfo;
use App\Services\SendVerifyToken;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PubSub;
use Spatie\Permission\Models\Role;

class CreateUserIDController extends Controller
{
    /**
     * Create new user for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/v2/create",
     *     summary="Create new user for One-Step 2.0",
     *     description="Verify phone number and handler to create new user for One-Step 2.0",
     *     tags={"OneStep 2.0 | User Account"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *              @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="Username for new user account",
     *                 required={"true"},
     *                 example="john.kiels"
     *             ),
     *              @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Save and Verify phone number of user",
     *                 required={"true"},
     *                 example="+4492838989290"
     *             ),
     *             @OA\Property(
     *                 property="messenger",
     *                 type="string",
     *                 description="OneStep user account verification messenger.",
     *                 example="Whatsapp"
     *             ),
     *             @OA\Property(
     *                 property="channel",
     *                 type="string",
     *                 description="OneStep user account OTP type or channel (SMS or Messenger).",
     *                 required={"true"},
     *                  example="sms"
     *             ),
     *             @OA\Property(
     *                 property="handler",
     *                 type="string",
     *                 description="OneStep user account verification handler.",
     *                 required={"true"},
     *                 example="@ultainfinity"
     *             ),
     *             @OA\Property(
     *                 property="referral_code",
     *                 type="string",
     *                 description="Referral code for user account",
     *                 required={"false"},
     *                 example="1827oGRL"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response="201",
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
     *                 example="Create new user"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="SMS verification code sent to +4492838989290"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     example="+4492838989290"
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     example="john.kiels"
     *                 ),
     *                 @OA\Property(
     *                     property="channel",
     *                     type="string",
     *                     example="sms"
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
     *                 example="Create new user"
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
     * @param SendVerifyToken $sendOTP
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function createAccount(Request $request, SendVerifyToken $sendOTP): JsonResponse
    {
        $validator = Validator::make($request->all(), VerifyStepInfo::rules());

        if ($validator->fails()) {
            return response()->jsonApi([
                'title' => 'Create new user',
                'message' => "Input validator errors. Try again.",
                'data' => $validator->errors()
            ], 422);
        }

        // Validate input date
        $input = (object)$validator->validated();

        //Select OTP destination
        if ($input->channel === 'sms') {
            $sendTo = $input->phone;
        } elseif ($input->channel === 'messenger') {
            $sendTo = $input->handler;
        }

        try {
            // Check whether user already exist
            $userExist = User::where([
                'phone' => $input->phone,
                'username' => $input->username
            ])->doesntExist();

            if ($userExist) {
                // Create verification token (OTP - One Time Password)
                $otpToken = VerifyStepInfo::generateOTP(6);

                // Generate token expiry time in minutes
                $validity = VerifyStepInfo::tokenValidity(30);

                // Create user Account
                $user = new User;
                $user->username = $input->username;
                $user->phone = $input->phone;
                $user->save();

                // Add Client Role to User
                $role = Role::firstOrCreate([
                    'name' => USER::INVESTOR_USER
                ]);
                $user->roles()->sync($role->id);

                // save verification token
                VerifyStepInfo::create([
                    'username' => $input->username,
                    'channel' => $input->channel,
                    'receiver' => $sendTo,
                    'code' => $otpToken,
                    'validity' => $validity
                ]);

                // Send verification token (SMS or Messenger)
                $sendOTP->dispatchOTP($input->channel, $sendTo, $otpToken);

                // Join new user to referral program
                $sendData = [
                    'user' => $user->toArray()
                ];

                if ($request->has('referral_code')) {
                    $sendData = [
                        // 'application_id' => $input['application_id'],
                        'referral_code' => $request->get('referral_code')
                    ];
                }
                PubSub::publish('NewUserRegistered', $sendData, config('pubsub.queue.referrals'));

                // Subscribing new user to Subscription service
                PubSub::publish('NewUserRegistered', [
                    'user' => $user->toArray(),
                ], config('pubsub.queue.subscriptions'));

                //Show response
                return response()->jsonApi([
                    'title' => 'Create new user',
                    'message' => "{$input->channel} verification code sent to {$sendTo}.",
                    'data' => [
                        'channel' => $input->channel,
                        'username' => $input->username,
                        'receiver' => $sendTo
                    ]
                ]);
            } else {
                return response()->jsonApi([
                    'title' => 'Create new user',
                    'message' => "{$sendTo} is already taken/verified by another user. Try again.",
                ], 400);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'Create new user',
                'message' => "Unable to send {$input->channel} verification code to {$sendTo}. Try again. " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resend OTP for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/v2/otp/resend",
     *     summary="Resend OTP for One-Step 2.0",
     *     description="Resend OTP to create new user for One-Step 2.0",
     *     tags={"OneStep 2.0 | User Account"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"receiver"},
     *
     *             @OA\Property(
     *                 property="receiver",
     *                 type="string",
     *                 description="Resend OTP channel for OneStep 2.0 user account.",
     *                 example="+4492838989290"
     *             ),
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="Resend OTP username for OneStep 2.0 user account.",
     *                 example="kiels.john"
     *             ),
     *             @OA\Property(
     *                 property="channel",
     *                 type="string",
     *                 description="Resend OTP channel for OneStep 2.0 user account.",
     *                 example="sms"
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
     *                 example="Create new user account step 1"
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
     * @param SendVerifyToken $sendOTP
     * @return JsonResponse
     * @throws ValidationException
     */
    public function resendOTP(Request $request, SendVerifyToken $sendOTP): JsonResponse
    {
        // Validate input date
        $validator = Validator::make($request->all(), [
            'receiver' => 'required|string',
            'channel' => 'required|string',
            'username' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->jsonApi([
                'title' => 'Resend OTP code',
                'message' => $validator->errors(),
            ], 422);
        }

        $input = (object)$validator->validated();

        try {
            // Create verification token (OTP - One Time Password)
            $token = VerifyStepInfo::generateOTP(6);

            //Generate token expiry time in minutes
            $validity = VerifyStepInfo::tokenValidity(30);

            // save verification token
            VerifyStepInfo::create([
                'username' => $input->username,
                'channel' => $input->channel,
                'receiver' => $input->receiver,
                'code' => $token,
                'validity' => $validity
            ]);

            // Send verification token (SMS or Messenger)
            $sendOTP->dispatchOTP($input->channel, $input->receiver, $token);

            //Show response
            return response()->jsonApi([
                'title' => 'Resend OTP code',
                'message' => "{$input->channel} verification code sent to {$input->receiver}.",
                'data' => [
                    'channel' => $input->channel,
                    'id' => $input->receiver
                ]
            ]);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'Resend OTP code',
                'message' => "Unable to send {$input->channel} verification code to {$input->receiver}. Try again. " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify new user OTP for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/v2/otp/verify",
     *     summary="Verify new user OTP for One-Step 2.0",
     *     description="Verify phone number or handler to create new user for One-Step 2.0",
     *     tags={"OneStep 2.0 | User Account"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Verify new user token for One-Step 2.0",
     *                 required={"token"},
     *                 example="h433ui6"
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
     *                 example="Create new user account step 1"
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
    public function verifyOTP(Request $request): JsonResponse
    {
        // Validate user input data
        $validator = Validator::make($request->all(), [
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->jsonApi([
                'title' => 'Verify OTP code',
                'message' => 'Input validator errors. Try again',
                'data' => $validator->errors()
            ], 422);
        }

        $input = (object)$validator->validated();

        try {
            // find the token
            $existQuery = VerifyStepInfo::where(['code' => $input->token]);

            // Check validity and availability
            if ($existQuery->exists()) {
                $userData = $existQuery->first();
                $username = $userData->username;
                $id = "{$username}@onestep.com";

                // User
                $user = User::where('username', $userData->username)->first();
                $token = $user->createToken($user->username)->accessToken;

                // Delete the token
                $existQuery->delete();

                //Send success response
                return response()->jsonApi([
                    'title' => 'Verify OTP code',
                    'message' => 'New user verification was successful',
                    'data' => [
                        'username' => $username,
                        'id' => $id,
                        'accessToken' => $token
                    ]
                ]);
            } else {
                // Send invalid token response
                return response()->jsonApi([
                    'title' => 'Verify OTP code',
                    'message' => 'New user verification FAILED. Try again',
                ], 400);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'Verify OTP code',
                'message' => "Unable to verify new use with token {$input->token}. Try again. " . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update new user personal info for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/v2/update",
     *     summary="Update new user personal info",
     *     description="Update new user for One-Step 2.0",
     *     tags={"OneStep 2.0 | User Account"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="New user username",
     *                 required={"username"},
     *                 example="john.kiels"
     *             ),
     *             @OA\Property(
     *                 property="fullname",
     *                 type="string",
     *                 description="New user full name update",
     *                 required={"fullname"},
     *                 example="Ben Jones"
     *             ),
     *             @OA\Property(
     *                 property="country",
     *                 type="string",
     *                 description="New user country update",
     *                 required={"country"},
     *                 example="United Kindom"
     *             ),
     *             @OA\Property(
     *                 property="address",
     *                 type="string",
     *                 description="New user address update",
     *                 required={"address"},
     *                 example="23 Kinston Street, Bolton"
     *             ),
     *             @OA\Property(
     *                 property="birthday",
     *                 type="string",
     *                 description="New user birthday update",
     *                 required={"birthday"},
     *                 example="2001/02/20"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="201",
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
     *                 example="New user personal info"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User personal data updated"
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
     *                 example="New user personal info"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User personal data update FAILED"
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
    public function updateUser(Request $request): JsonResponse
    {
        // Validate user input data
        $validator = Validator::make($request->all(), User::rules());

        if ($validator->fails()) {
            return response()->jsonApi([
                'title' => 'New user personal info',
                'message' => "Input validator errors. Try again.",
                'data' => $validator->errors()
            ], 422);
        }

        $input = (object)$validator->validated();

        try {
            // Update user account
            $user = User::where('username', $input->username)->first();
            $names = explode(" ", $input->fullname);

            if (is_array($names) && count($names) >= 2) {
                $firstname = $names[0];
                $lastname = $names[1];
            } else {
                $firstname = $input->fullname;
                $lastname = null;
            }

            $user->first_name = $firstname;
            $user->last_name = $lastname;
            $user->address_country = $input->country;
            $user->address_line1 = $input->address;
            $user->birthday = $input->birthday;

            if ($user->save()) {
                // Return response
                return response()->jsonApi([
                    'title' => 'New user personal info',
                    'message' => 'User account was successful updated',
                    'data' => [
                        'username' => $input->username
                    ]
                ]);
            } else {
                return response()->jsonApi([
                    'title' => 'New user personal info',
                    'message' => 'User account was NOT updated',
                ], 400);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'New user personal info',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Save new user recovery questions for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/v2/update/recovery",
     *     summary="Update new user recovery questions",
     *     description="Update new user recovery questions for One-Step 2.0",
     *     tags={"OneStep 2.0 | User Account"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="New user  recovery username",
     *                 required={"username"},
     *                 example="richard.brown"
     *             ),
     *              @OA\Property(
     *                 property="answer1",
     *                 type="string",
     *                 description="New user  recovery question 1",
     *                 required={"question1"},
     *                 example="Kathrine"
     *             ),
     *             @OA\Property(
     *                 property="answer2",
     *                 type="string",
     *                 description="New user  recovery question 2",
     *                 required={"question2"},
     *                 example="Mikky"
     *             ),
     *              @OA\Property(
     *                 property="answer3",
     *                 type="string",
     *                 description="New user  recovery question 3",
     *                 required={"question3"},
     *                 example="United Kindom"
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
     *                 example="New user recovery questions"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Create new user account step 1"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *                 example=""
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response="404",
     *          description="User Account Not Found",
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
     *                 example="New user recovery questions"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User account not found."
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
    public function updateRecoveryQuestion(Request $request): JsonResponse
    {
        // Validate user input data
        $validator = Validator::make($request->all(), RecoveryQuestion::rules());

        if ($validator->fails()) {
            return response()->jsonApi([
                'title' => 'New user recovery questions',
                'message' => 'Input validator errors. Try again.',
                'data' => $validator->errors()
            ], 422);
        }

        $input = (object)$validator->validated();

        try {
            // Update user account
            $userQuery = User::where('username', $input->username);

            // Does the user account exist?
            if ($userQuery->exists()) {
                //get the user ID
                $user = $userQuery->first();

                RecoveryQuestion::firstOrCreate([
                    'user_id' => $user->id,
                    'answer_one' => $input->answer1,
                    'answer_two' => $input->answer2,
                    'answer_three' => $input->answer3
                ]);

                // Generate user access token
                $token = $user->createToken($user->username)->accessToken;

                return response()->jsonApi([
                    'title' => 'New user recovery questions',
                    'message' => 'User account security questions was successful saved',
                    'data' => [
                        'username' => $input->username,
                        'access_token' => $token
                    ]
                ]);
            } else {
                return response()->jsonApi([
                    'title' => 'New user recovery questions',
                    'message' => 'User account was not found!'
                ], 404);
            }
        } catch (ModelNotFoundException $ex) {
            return response()->jsonApi([
                'title' => 'New user recovery questions',
                'message' => $ex->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'New user recovery questions',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
