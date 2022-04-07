<?php

namespace App\Api\V1\Controllers\OneStepId;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TwoFactorAuth;
use Illuminate\Support\Facades\DB;
use App\Api\V1\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SendTokenSmsToUser extends Controller
{  
    
 
   
     /**
     * Create new user for One-Step
     *
     * @OA\Post(
     *     path="/auth/send-sms",
     *     summary="Create new user for One-Step",
     *     description="Create new user for One-Step",
     *     tags={"One-Step Users"},
     *
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"phone"},
     *
     *             @OA\Property(
     *                 property="phone",
     *                 type="number",
     *                 description="Phone number of user",
     *                 example="380971829100"
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
     *                 example="Create new user. Step 1"
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
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request,$botID)
    { 
        // ...
        // Validate input data
        $this->validate($request, [
            'phone_number' => 'required|integer',
        ]);
        try {
            
            $user = User::where("phone_number", $request->phone_number)->firstOrFail();
            // user already exists
            if( $user->status == User::STATUS_BANNED)
            {
                 
                return response()->json([
                     "phone_exists" => true,
                     "user_status" => $user->status,
                     "type" => "danger",
                     "message" => "This user has been banned from this platform."
                ],403);

            }
        } catch (ModelNotFoundException $e) {
            //Phone Number Does not exist
            return response()->json([
                "message" => "This phone number does not exist",
                "phone_exists" => false,
                "type" => "danger"
           ], 400);
        }


        // User is either active or inactive, we send token
        try {

            $token = TwoFactorAuth::generateToken();
            
            $sid = $this->sendSms($token,$request->phone_number);

            $twoFa = TwoFactorAuth::create([
                    "sid" => $sid,
                    "user_id" => $user->id,
                    "code" => $token
            ]);

            // Return response
            return response()->json([
                'type' => 'success',
                'message' => 'A token SMS has been sent to your phone number',
                "phone_exists" => true,
                "user_status" => $user->status,
                'sid' => $sid,
                // TODO Remove this before shipping
                "test_purpose_token" => $token
            ], 200);


        } catch (Exception $e) {
  
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to send sms to phone number. Try again.",
                "phone_exists" => true
            ], 400);
        }
    }

}