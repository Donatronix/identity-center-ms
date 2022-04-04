<?php

namespace App\Api\V1\Controllers\OneStepId;

use App\Models\User;
use App\Api\V1\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserRequestsRegistrationByPhoneNumber extends Controller
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
            'phone_number' => 'required|integer',
        ]);
        

        try {

            $user = User::where("phone_number", $request->phone)->firstOrFail();
            

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
            else if ($user->status == User::STATUS_INACTIVE) 
            {
                return response()->json([
                     "code" => 200,
                     "message" => "This user already exists. Required send verification code",
                     "phone_exists" => true,
                     "user_status" => $user->status,
                     "type" => "success"
                ],200);

            }else if ($user->status == User::STATUS_ACTIVE)
            {
                 
                return response()->json([
                     "code" => 200,
                     "message" => "This user already exists.",
                     "phone_exists" => true,
                     "user_status" => $user->status,
                     "type" => "success"
                ], 200);
            }


        } catch (ModelNotFoundException $e) {
    
            //pass
            
        }

        // user does  not exist
        try {
            
            // send sms here

            $user = User::create([
                "phone" => $request->phone,
                "status" => User::STATUS_INACTIVE
           ]);
            
            // Return response
            return response()->json([
                'type' => 'success',
                'title' => "Create new user. Step 1",
                'message' => 'User was successful created',
                'id' => $user->id
            ], 201);


        } catch (Exception $e) {

            return response()->json([
                'type' => 'danger',
                'title' => "Create new user. Step 1",
                'message' => $e->getMessage()
            ], 400);
        }
        
    }
  

}