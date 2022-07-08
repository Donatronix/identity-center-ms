<?php

namespace App\Api\V1\Controllers\Public\OneStepId2;

use App\Api\V1\Controllers\Controller;
use App\Models\RecoveryQuestion;
use App\Models\User;
use App\Models\VerifyStepInfo;
use App\Services\SendVerifyToken;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
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
     *                 example="Create new user account One-Step 2.0"
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
                'type' => 'danger',
                'message' => "Input validator errors. Try again.",
                "data" => null
            ], 400);
        }
        
        //validate input date
        $input = $validator->validated();

        //Receiver token
        $sendto = $this->getTokenReceiver($input);
        $channel = $input['channel'];

        try {
            // Check whether user already exist
            $userExist = User::where([
                'phone' => $input['phone'],
                'username' => $input['username']
            ])->doesntExist();

            if ($userExist) {
                // Create verification token (OTP - One Time Password)
                $otpToken = VerifyStepInfo::generateOTP(7);

                //Generate token expiry time in minutes
                $validity = VerifyStepInfo::tokenValidity(30);

                //Create user Account
                $user = new User;
                $user->username = $input['username'];
                $user->phone = $input['phone'];
                $user->save();

                /**
                 * Add Client Role to User
                 *
                 */
                $role = Role::firstOrCreate([
                    'name' => USER::CLIENT_USER
                ]);
                $user->roles()->sync($role->id);

                //Other response data array
                $data['channel'] = $input['channel'];
                $data['username'] = $input['username'];
                $data['receiver'] = $sendto;

                // save verification token
                VerifyStepInfo::create([
                    'username' => $input['username'],
                    'channel' => $input['channel'],
                    'receiver' => $sendto,
                    'code' => $otpToken,
                    'validity' => $validity
                ]);

                // Send verification token (SMS or Massenger)
                $sendOTP->dispatchOTP($input['channel'], $sendto, $otpToken);

                //Show response
                return response()->jsonApi([
                    'type' => 'success',
                    'message' => "{$channel} verification code sent to {$sendto}.",
                    "data" => $data
                ], 200);

            } else {
                return response()->jsonApi([
                    'type' => 'danger',
                    'message' => "{$sendto} is already taken/verified by another user. Try again.",
                    "data" => null
                ], 400);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'message' => "Unable to send {$input['channel']} verification code to {$sendto}. Try again.",
                "data" => $e->getMessage()
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
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
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
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function resendOTP(Request $request, SendVerifyToken $sendOTP): JsonResponse
    {
        //validate input date
        $input = $this->validate($request, [
            'receiver' => 'required|string',
            'channel' => 'required|string',
            'username' => 'required|string'
        ]);

        $sendto = $input['receiver'];
        $channel = $input['channel'];

        try {
            // Create verification token (OTP - One Time Password)
            $token = VerifyStepInfo::generateOTP(7);

            //Generate token expiry time in minutes
            $validity = VerifyStepInfo::tokenValidity(30);

            // save verification token
            VerifyStepInfo::create([
                'username' => $input['username'],
                'channel' => $input['channel'],
                'receiver' => $sendto,
                'code' => $token,
                'validity' => $validity
            ]);

            // Send verification token (SMS or Massenger)
            $sendOTP->dispatchOTP($input['channel'], $sendto, $token);

            //Show response
            return response()->jsonApi([
                'type' => 'success',
                'message' => "{$channel} verification code sent to {$sendto}.",
                "data" => ['channel' => $input['channel'], 'id' => $sendto]
            ], 200);

        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'message' => "Unable to send {$input['channel']} verification code to {$sendto}. Try again.",
                "data" => $e->getMessage()
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
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
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
        $input = $this->validate($request, ['token' => 'required|string']);

        try {
            //find the token
            $existQuery = VerifyStepInfo::where(['code' => $input['token']]);

            //Check validity and availability
            if ($existQuery->exists()) {
                $userData = $existQuery->first();
                $username = $userData->username;
                $id = "{$username}@onestep.com";

                //Delete the token
                $existQuery->delete();

                //Send success response
                return response()->jsonApi([
                    'type' => 'success',
                    'message' => "New user verification was successful.",
                    "data" => [
                        'username' => $username,
                        'id' => $id
                    ]
                ], 200);
            } else {
                //Send invalid token response
                return response()->jsonApi([
                    'type' => 'danger',
                    'message' => "New user verification FAILED. Try again.",
                    "data" => null
                ], 400);
            }
        } catch (Exception $e) {
            // Error occured
            return response()->jsonApi([
                'type' => 'danger',
                'message' => "Unable to verify new use with token {$input['token']}. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Create new user for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/v2/update",
     *     summary="Update new user personal info",
     *     description="Update new user for One-Step 2.0",
     *     tags={"OneStep 2.0 | User Account"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
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
        $input = $request->all();

        $validator = Validator::make($input, User::rules());

        if ($validator->fails()) {
            return response()->jsonApi([
                'type' => 'danger',
                'message' => "Input validator errors. Try again.",
                "data" => null
            ], 400);
        }

        try {
            // Update user account
            $user = User::where('username', $input['username'])->first();
            $names = explode(" ", $input['fullname']);

            if (is_array($names) && count($names) >= 2) {
                $firstname = $names[0];
                $lastname = $names[1];
            } else {
                $firstname = $input['fullname'];
                $lastname = null;
            }

            $user->first_name = $firstname;
            $user->last_name = $lastname;
            $user->address_country = $input['country'];
            $user->address_line1 = $input['address'];
            $user->birthday = $input['birthday'];

            if ($user->save()) {
                // Return response
                return response()->jsonApi([
                    'type' => 'success',
                    'title' => "Update user account step 3",
                    'message' => 'User account was successful updated',
                    'data' => ['username' => $input['username']]
                ], 200);
            } else {
                return response()->jsonApi([
                    'type' => 'danger',
                    'title' => "Update user account step 1",
                    'message' => 'User account was NOT  updated',
                    'data' => null
                ], 400);
            }

        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Update user account",
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
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
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
        $input = $request->all();

        $validator = Validator::make($input, RecoveryQuestion::rules());

        if ($validator->fails()) {
            return response()->jsonApi([
                'type' => 'danger',
                'message' => "Input validator errors. Try again.",
                "data" => null
            ], 400);
        }

        try {
            // Update user account
            $userQuery = User::where('username', $input['username']);

            //Does the user account exist?
            if ($userQuery->exists()) {
                //get the user ID
                $user = $userQuery->first();
                $userId = $user->id;

                //Save recovery question
                $question = new RecoveryQuestion;
                $question->user_id = $userId;
                $question->answer_one = $input['answer1'];
                $question->answer_two = $input['answer2'];
                $question->answer_three = $input['answer3'];

                 // Generate user access token
                 $token = $user->createToken($user->username)->accessToken;

                if ($question->save()) {
                    // Return response
                    return response()->jsonApi([
                        'type' => 'success',
                        'message' => 'User account security questions was successful saved',
                        'data' => [
                                'username' => $input['username'],
                                'access_token' => $token
                            ]
                    ], 200);
                } else {
                    return response()->jsonApi([
                        'type' => 'danger',
                        'message' => 'User account security questions was NOT  saved',
                        'data' => null
                    ], 400);
                }
            } else {
                return response()->jsonApi([
                    'type' => 'danger',
                    'message' => 'User account was not found!',
                    'data' => null
                ], 404);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'message' => $e->getMessage(),
                'data' => $input
            ], 400);
        }
    }

    /**
     * Get the OTP reciever based on channel
     *
     * @param array $input
     *
     * @return string
     */
    private function getTokenReceiver(array $input): string
    {
        //Select OTP destination
        if ($input['channel'] === 'sms') {
            return $input['phone'];
        } elseif ($input['channel'] === 'messenger') {
            return $input['handler'];
        }
    }
}
