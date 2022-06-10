<?php

namespace App\Api\V1\Controllers\OneStepId2;

use App\Api\V1\Controllers\Controller;
use App\Exceptions\SMSGatewayException;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class UserProfileController extends Controller
{
    /**
     * Get user profile for One-Step 2.0
     *
     * @OA\Get(
     *     path="/user-profile/{id}/details",
     *     summary="Get user profile for One-Step 2.0",
     *     description="Get user profile for One-Step 2.0",
     *     tags={"User Profile by OneStep 2.0"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="One-Step user ID",
     *          required=true,
     *          example="373458be-3f01-40ca-b6f3-245239c7889f",
     *          @OA\Schema(
     *              type="string"
     *          ),
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
     *                 example="User profile info retrieved successfully."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                     example="John"
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                     example="Kiels"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     example="Kiels@onestep.com"
     *                 ),
     *                 @OA\Property(
     *                     property="country",
     *                     type="string",
     *                     example="United Kindom"
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
     *                 example="User profile for One-Step 2.0 not found."
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
     * @param string $id
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getProfile(string $id): JsonResponse
    {
        try{
           // Check whether user already exist
           $userQuery = User::where(['id'=>$id]);
               
            if($userQuery->exists()){
                // Fetch user profile
                $user = $userQuery->select(
                        'users.first_name',
                        'users.last_name',
                        'users.email',
                        'users.address_country',
                        'users.local'
                    )->first();

                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "User profile retrieved successfully.",
                    "data" =>$user->toArray()], 200);

            }else{
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to retrieve user profile.",
                "data" => null
            ], 400);
        }  
    }

    /**
     * Get user profile for One-Step 2.0
     *
     * @OA\Get(
     *     path="/user-profile/update/{id}",
     *     summary="Get user profile update info",
     *     description="Get user profile update info for One-Step 2.0",
     *     tags={"User Profile by OneStep 2.0"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="One-Step user ID",
     *          required=true,
     *          example="373458be-3f01-40ca-b6f3-245239c7889f",
     *          @OA\Schema(
     *              type="string"
     *          ),
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
     *                 example="User profile info retrieved successfully."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object"
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
     *                 example="User profile for One-Step 2.0 not found."
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
     * @param int $id
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateInfo(string $id): JsonResponse
    {
        try{
            // Check whether user already exist
            $userQuery = User::where(['id'=>$id]);
                
             if($userQuery->exists()){
                 // Fetch user profile
                 $user = $userQuery->first();
 
                 //Show response
                 return response()->json([
                     'type' => 'success',
                     'message' => "User info retrieved successfully.",
                     "data" =>$user->toArray()], 200);
 
             }else{
                 return response()->json([
                     'type' => 'danger',
                     'message' => "User info does NOT exist.",
                     "data" => null
                 ], 400);
             }
        }catch(Exception $e){
            return response()->json([
                 'type' => 'danger',
                 'message' => "Unable to retrieve user info.",
                 "data" => null
            ], 400);
        }
      
    }

    /**
     * Update user profile for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/update",
     *     summary="Update user profile",
     *     description="Update user profile for One-Step 2.0",
     *     tags={"User Profile by OneStep 2.0"},
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
     *                 description="User ID for user profile update",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *              @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="User email for user profile update",
     *                 required={"true"},
     *                 example="kiels@ultainfinity.com"
     *             ),
     *             @OA\Property(
     *                 property="first_name",
     *                 type="string",
     *                 description="User first name for user profile update",
     *                 example="john"
     *             ),
     *             @OA\Property(
     *                 property="last_name",
     *                 type="string",
     *                 description="User last name for user profile update",
     *                 required={"true"},
     *                  example="kiels"
     *             ),
     *             @OA\Property(
     *                 property="country",
     *                 type="string",
     *                 description="User country for user profile update",
     *                 required={"true"},
     *                  example="United Kindom"
     *             ),
     *             @OA\Property(
     *                 property="language",
     *                 type="string",
     *                 description="User language for user profile update",
     *                 required={"true"},
     *                 example="English"
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
     *                 example="User profile update was successful"
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
     *                 example="User profile update was FAILED"
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
    public function updateSave(Request $request): JsonResponse
    {
      //validate input date
      $input = $this->validate($request,[
        'id'=> 'required|numeric',
        'email'=> 'required|string|email',
        'first_name'=>'required|string',
        'last_name'=> 'required|string',
        'country'=> 'required|string',
        'language'=> 'required|string'
      ]);

       try{
           // Check whether user already exist
           $userQuery = User::where(['id'=> $input['id']]);
               
            if($userQuery->exists()){
                
                //Create user Account
                $user = $userQuery->first();
                $user->first_name = $input['first_name'];
                $user->last_name = $input['last_name'];
                $user->email = $input['email'];
                $user->country = $input['country'];
                $user->local = $input['language'];
               
                if($user->save()){
                    //Show response
                    return response()->json([
                        'type' => 'success',
                        'message' => "User profile update was successful."
                    ], 400);
                }else{
                    return response()->json([
                        'type' => 'danger',
                        'message' => "User profile update was FAILED."
                    ], 400);
                }
            }else{
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update user profile.",
                "data" => null
            ], 400);
        }
      
    }
}