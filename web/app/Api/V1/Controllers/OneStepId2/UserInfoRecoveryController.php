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
use Illuminate\Validation\ValidationException;
use App\Services\SendVerifyToken;

class UserInfoRecoveryController extends Controller
{

    /**
     * User account recovery information for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/recovery/userinfo",
     *     summary="Recover user account for One-Step 2.0",
     *     description="Receive user account recovery info for One-Step 2.0",
     *     tags={"User Account Recovery by OneStep 2.0"},
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
     *                 property="id",
     *                 type="string",
     *                 description="OneStep ID user account",
     *                 required={"true"},
     *                 example="john.kiels@onestep.com"
     *             ),
     *              @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Verify phone number of user",
     *                 required={"true"},
     *                 example="+4492838989290"
     *             ),
     *             @OA\Property(
     *                 property="handler",
     *                 type="string",
     *                 description="User account verification handler.",
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
     *                 property="message",
     *                 type="string",
     *                 example="User account account recovery."
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
    public function recoveryInfo(Request $request, SendVerifyToken $sendOTP): JsonResponse
    {
      //validate input date
      $input = $this->validate($request, [
        'id'=>'required|string',
        'phone'=>'nullable|string|max:20',
        'handler'=>'nullable|string',
      ]);
      
       try{
           // Check whether user already exist
           $idArr = explode("@",$input['id']);
           $username = $idArr[0];

           $userQuery = User::where('phone',$input['phone'])
                              ->orWhere('username', $username);
               
            if($userQuery->exists()){
                //Retrieve user info
                $user = $userQuery->first();
                $sendto = $user->phone;
                $channel = 'sms'; 

                // Create verification token (OTP - One Time Password)
                $token = VerifyStepInfo::generateOTP(7);
                
                //Generate token expiry time in minutes
                $validity = VerifyStepInfo::tokenValidity(30);
                
                // save verification token
                VerifyStepInfo::create([
                    'username'=>$username,
                    'channel'=>$channel,
                    'receiver'=>$sendto,
                    'code'=>$token,
                    'validity'=>$validity
                ]);

                // Send verification token (SMS or Massenger)
                $sendOTP->dispatchOTP($channel, $sendto, $token);
                
                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "{$channel} verification code sent to {$sendto}.",
                    "data" => [
                            'channel'=>$channel, 
                            'username'=>$username,
                            'receiver'=>$sendto 
                        ]
                ], 200);

            }else{
                return response()->json([
                    'type' => 'danger',
                    'message' => "User account does not exist. Try again.",
                    "data" => null
                ], 400);
            }
        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to send token for verification. Try again.",
                "data" => null
            ], 400);
        }
      
    }

    /**
     * Verify user account OTP for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/recovery/otp/verify",
     *     summary="Recover user account for One-Step 2.0",
     *     description="Verify phone number or handler to recover user account for One-Step 2.0",
     *     tags={"User Account Recovery by OneStep 2.0"},
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
     *                 description="Verify user account recovery token for One-Step 2.0",
     *                 required={"token"},
     *                 example="f5j33oi"
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
     *                 property="message",
     *                 type="string",
     *                 example="User account account recovery."
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
       $input = $this->validate($request, ['token'=>'required|string']);
       
       try{
            //find the token
            $existQuery = VerifyStepInfo::where(['code'=>$input['token']]);
            
            //Check validity and availability
            if($existQuery->exists()){
                $userData = $existQuery->first();
                $username = $userData->username;
                $id = "{$username}@onestep.com";
                
                //Delete the token
                $existQuery->delete();
                
                //Send success response
                return response()->json([
                    'type' => 'success',
                    'message' => "User account verification was successful.",
                    "data" => ['username'=>$username, 'id'=>$id]
                ], 200);
            }else{
                //Send invalid token response
                return response()->json([
                    'type' => 'danger',
                    'message' => "User account verification FAILED. Try again.",
                    "data" => null
                ], 400);
            }
       }catch(Exception $e){
            // Error occured
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to verify new use with token {$input['token']}. Try again.",
                "data" => null
            ], 400);
       }

    }

    /**
     * Verify user account recovery questions for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/recovery/questions",
     *     summary="Get user account recovery questions",
     *     description="Verify user account recovery questions for One-Step 2.0",
     *     tags={"User Account Recovery by OneStep 2.0"},
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
     *                 description="User account recovery username",
     *                 required={"username"},
     *                 example="richard.brown"
     *             ),
     *              @OA\Property(
     *                 property="question1",
     *                 type="string",
     *                 description="User account  recovery question 1",
     *                 required={"question1"},
     *                 example="Kathrine"
     *             ),
     *             @OA\Property(
     *                 property="question2",
     *                 type="string",
     *                 description="User account  recovery question 2",
     *                 required={"question2"},
     *                 example="Mikky"
     *             ),
     *              @OA\Property(
     *                 property="question3",
     *                 type="string",
     *                 description="User account  recovery question 3",
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
     *                 property="message",
     *                 type="string",
     *                 example="User account account recovery."
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
    public function recoveryQuestions(Request $request): JsonResponse
    {
        // Validate user input data
        $input = $this->validate($request, [
                    'username'=>'required|string',
                    'question1'=>'required|string',
                    'question2'=>'required|string',
                    'question3'=>'required|string'
                ]);
       
        try {
            // Update user account
            $user = User::where('username', $input['username'])->first();
            
            //$user->question = $input['question1'];
            
            if($user->save()){
                // Return response
                return response()->json([
                    'type' => 'success',
                    'title' => "Update user account step 3",
                    'message' => 'User account was successful updated',
                    'data' => ['username'=>$input['username']]
                ], 200);
            }else{
                return response()->json([
                    'type' => 'danger',
                    'title' => "Update user account step 1",
                    'message' => 'User account was NOT  updated',
                    'data' => null
                ], 400);
            }
            
        } catch (Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => "Update user account",
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Send user account recovered ID for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/recovery/sendid",
     *     summary="User account recovered ID",
     *     description="Send user account recovered ID for One-Step 2.0",
     *     tags={"User Account Recovery by OneStep 2.0"},
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
     *                 property="phone",
     *                 type="string",
     *                 description="Send recovered ID to phone",
     *                 example="+448594048303"
     *             ),
     *              @OA\Property(
     *                 property="messenger",
     *                 type="string",
     *                 description="Send recovered ID to messenger",
     *                 example="whatsapp"
     *             )
     *         )
     *     ),
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
     *                 property="message",
     *                 type="string",
     *                 example="Your OneStep ID has been sent to your phone/whatsapp."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *                 example="john.kiels@onestep.com"
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
     *                 property="message",
     *                 type="string",
     *                 example="Your OneStep ID was unsuccessful."
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
    public function sendRecoveredID(Request $request): JsonResponse
    {
        
    }

}
