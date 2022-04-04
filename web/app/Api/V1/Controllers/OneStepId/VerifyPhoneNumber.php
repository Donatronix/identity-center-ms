<?php

namespace App\Api\V1\Controllers\OneStepId;

use Exception;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\TwoFactorAuth;
use App\Api\V1\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VerifyPhoneNumber extends Controller
{  
    
 
  
     /**
     * Create new user for One-Step
     *
     * @OA\Post(
     *     path="/auth/send-phone",
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
    public function __invoke(Request $request)
    {
        // ...
        // Validate input data
        $this->validate($request, [
            'token' => 'required',
        ]);

        try {
            
            $twoFa = TwoFactorAuth::where("code",$request->token)->firstOrFail();
        
        } catch ( ModelNotFoundException $th) {

            return response()->json([ 
                "type" => "danger",
                "message" => "Invalid Token",
                "validate_auth_code" => false
            ], 400);
        }


        try {
            
            $user = $twoFa->user;

            if($user->status == User::STATUS_BANNED){
                 
                return response()->json([
                    "type" => "danger",
                    "user_status" => $user->status,
                    "sid" => $twoFa->sid,
                    "message" => "User has been banned from this platform."     
                ],403);
            }

            $user->phone_number_verified_at = Carbon::now();
            $user->save();

        } catch (Exception $th) {
            //throw $th;

            return response()->json([
               "message" => "Unable to verify token",
               "type" => "danger"
            ],400);

        }


        return response()->json([
            "message" => "Phone Number Verification successful",
            "type" => "success",
            "sid" => $twoFa->sid,
            "user_status" => $user->status,
            "validate_auth_code" => true
    
        ]);
   
        


        

    }
  

}