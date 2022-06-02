<?php

namespace App\Api\V1\Controllers\OneStepId2;

use App\Api\V1\Controllers\Controller;
use App\Exceptions\SMSGatewayException;
use App\Models\VerifyStepInfo;
use App\Models\User;
use Exception;
use PubSub;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateUserIDController extends Controller
{
    
    /**
     * Create new user for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/create",
     *     summary="Create new user for One-Step 2.0",
     *     description="Verify phone number and handler to create new user for One-Step 2.0",
     *     tags={"User Account by OneStep 2.0"},
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
     *          response=201,
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
     *                     property="channel",
     *                     type="string",
     *                     example="sms"
     *                 )
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
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function createAccount(Request $request): JsonResponse
    {
      //validate input date
      $input = $this->validate($request, VerifyStepInfo::roles());
      
       try{
            $sendto = null;
            // $channel = $request->input('channel');
            // $phone = $request->input('phone');
            // $handler = $request->input('handler');
            // $messenger = $request->input('messenger');
            
            // Check whether user already exist
            $userExist = User::where('phone', $input['phone'])->doesntExist();
            
            if($userExist){
                // Create verification token (OTP - One Time Password)
                $token = VerifyStepInfo::generateOTP();

                //Generate token expiry time in minutes
                $validity = time()+(60*60);

                //Select OTP destination
                if($input['channel']==='sms'){
                    $sendto = $input['phone'];
                }elseif($input['channel']==='messenger'){
                    $sendto = $input['handler'];
                }

                //Create user Account
                $user = new User;
                $user->username = $input['username'];
                $user->phone = $input['phone'];
                $user->save();

                // Send verification token (SMS or Massenger)
                PubSub::transaction(function () use ($input, $token, $sendto, $validity) {
                    // save verification token
                    VerifyStepInfo::create([
                        'channel'=>$input['channel'],
                        'receiver'=>$sendto,
                        'code'=>$token,
                        'validity'=>$validity
                    ]);
                })->publish('SendSMS', [
                    'to' => $sendto,
                    'instance' => $input['channel'],
                    'message' => $token,
                ], 'new_user_verify');
                
                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "{$channel} verification code sent to {$sendto}.",
                    "data" => ['channel'=>$input['channel'], 'id'=>$sendto]
                ], 200);

            }else{
                return response()->json([
                    'type' => 'danger',
                    'message' => "{$sendto} is already taken/verified by another user. Try again.",
                    "data" => null
                ], 400);
            }
        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to send {$input['channel']} verification code to {$sendto}. Try again.",
                "data" => null
            ], 400);
        }
      
    }

    /**
     * Resend OTP for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/otp/resend",
     *     summary="Resend OTP for One-Step 2.0",
     *     description="Resend OTP to create new user for One-Step 2.0",
     *     tags={"User Account by OneStep 2.0"},
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
     *                 description="Resent OTP channel for OneStep 2.0 user account.",
     *                 example="+4492838989290"
     *             ),
     *             @OA\Property(
     *                 property="channel",
     *                 type="string",
     *                 description="Resent OTP channel for OneStep 2.0 user account.",
     *                 example="sms"
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
    public function  resendOTP(Request $request): JsonResponse
    {
       //validate input date
       $input = $this->validate($request, [
           'receiver'=>'required|string',
           'channel'=>'required|string'
       ]);
      
        try{
            // Create verification token (OTP - One Time Password)
            $token = VerifyStepInfo::generateOTP();

            //Generate token expiry time in minutes
            $validity = time()+(60*60);

            $sendto = $input['receiver'];
            
            // Send verification token (SMS or Massenger)
            PubSub::transaction(function () use ($input, $token, $validity) {
                // save verification token
                VerifyStepInfo::create([
                    'channel'=>$input['channel'],
                    'receiver'=>$input['receiver'],
                    'code'=>$token,
                    'validity'=>$validity
                ]);
            })->publish('SendSMS', [
                'to' => $sendto,
                'instance' => $input['channel'],
                'message' => $token,
            ], 'new_user_verify');
            
            //Show response
            return response()->json([
                'type' => 'success',
                'message' => "{$channel} verification code sent to {$sendto}.",
                "data" => ['channel'=>$input['channel'], 'id'=>$sendto]
            ], 200);

        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to send {$channel} verification code to {$sendto}. Try again.",
                "data" => null
            ], 400);
        }
    }

    /**
     * Verify new user OTP for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/otp/verify",
     *     summary="Create new user for One-Step 2.0",
     *     description="Verify phone number or handler to create new user for One-Step 2.0",
     *     tags={"User Account by OneStep 2.0"},
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
     *                 property="token",
     *                 type="number",
     *                 description="Save and Verify phone number of user",
     *                 required={"phone"},
     *                 example="9398303039"
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
    public function  verifyOTP(Request $request): JsonResponse
    {
       // Validate user input data
       $input = $this->validate($request, ['token'=>'required|numeric|max:10']);
      
       try{
          
       }catch(Exception $e){
          
       }

    }

    /**
     * Create new user for One-Step 2.0
     *
     * @OA\Patch(
     *     path="/user-account/update",
     *     summary="Update new user personal info",
     *     description="Update new user for One-Step 2.0",
     *     tags={"User Account by OneStep 2.0"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
     *     @OA\Response(
     *          response=201,
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
     *                 example="Create new user. Step 1"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User was successful created"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     example="50000005-5005-5005-5005-500000000005"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="number",
     *                     example="380971829100"
     *                 )
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
    public function updateUser(Request $request): JsonResponse
    {
        // Validate user input data
        $this->validate($request, User::roles());

        
        try {
            // Try to create new user account
            $user = User::update(User::roles());
            
            if($user){
                // Return response
                return response()->json([
                    'type' => 'success',
                    'title' => "Create new user account step 3",
                    'message' => 'User account was successful updated',
                    'data' => $user
                ], 200);
            }else{
                return response()->json([
                    'type' => 'danger',
                    'title' => "Create new user account step 1",
                    'message' => 'User account was NOT created',
                    'data' => $user
                ], 400);
            }
            
        } catch (Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => "Create new user. Step 1",
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Save new user recovery questions for One-Step 2.0
     *
     * @OA\Patch(
     *     path="/user-account/update/recovery",
     *     summary="Update new user recovery questions",
     *     description="Update new user recovery questions for One-Step 2.0",
     *     tags={"User Account by OneStep 2.0"},
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
     *                 property="question1",
     *                 type="string",
     *                 description="New user  recovery question 1",
     *                 required={"question1"},
     *                 example="Kathrine"
     *             ),
     *             @OA\Property(
     *                 property="question2",
     *                 type="string",
     *                 description="New user  recovery question 2",
     *                 required={"question2"},
     *                 example="Mikky"
     *             ),
     *              @OA\Property(
     *                 property="question3",
     *                 type="string",
     *                 description="New user  recovery question 3",
     *                 required={"question3"},
     *                 example="United Kindom"
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
    public function updateRecoveryQuestion(Request $request): JsonResponse
    {
        // Validate user input data
        
        try {
        
        } catch (Exception $e) {
           
        }
    }
}
