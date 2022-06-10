<?php

namespace App\Api\V1\Controllers\OneStepId2;

use App\Api\V1\Controllers\Controller;
use App\Exceptions\SMSGatewayException;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


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
                    )->findOrFail();

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
        }catch(ModelNotFoundException $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to retrieve user profile.",
                "data" => $e->getMessage()
            ], 400);
        }  
    }

    /**
     * Change user profile password for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/password/change",
     *     summary="Change user password",
     *     description="Change user profile password for One-Step 2.0",
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
     *             @OA\Property(
     *                 property="id",
     *                 type="string",
     *                 description="User ID for user profile update",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *             @OA\Property(
     *                 property="current_password",
     *                 type="string",
     *                 description="Current user password for profile update",
     *                 required={"true"},
     *                 example="XXXXXXXX"
     *             ),
     *             @OA\Property(
     *                 property="new_password",
     *                 type="string",
     *                 description="New user password for profile update",
     *                 required={"true"},
     *                 example="XXXXXXXX"
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
     *                 example="User profile password changed successfully."
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
     *                 example="Unable to change profile password."
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
    public function updatePassword(Request $request): JsonResponse
    {
        $validData = $this->validate($request, [
            'id'=> 'required|string',
            'current_password'=> 'required|string|max:32',
            'new_password'=> 'required|string|max:32'
        ]);
        
        try{
            // Verify current password
            $userQuery = User::where('id',$validData['id']);
            
            $user = $userQuery->firstOrFail();
                
             if(Hash::check($validData['current_password'], $user->password )){

                $newPass = Hash::make($validData['new_password']);

                // Update user password
                $userQuery->update([
                    'password'=>$newPass
                ]);

                //TODO:send email to user
 
                 //Show response
                 return response()->json([
                     'type' => 'success',
                     'message' => "User password updated successfully.",
                     "data" =>null
                ], 200);
 
             }else{
                 return response()->json([
                     'type' => 'danger',
                     'message' => "Invalid user password. Try again",
                     "data" => null
                 ], 400);
             }
        }catch(ModelNotFoundException $e){
            return response()->json([
                 'type' => 'danger',
                 'message' => "Unable to update user password.",
                 "data" => $e->getMessage()
            ], 400);
        }
      
    }

    /**
     * Update username for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/username/update",
     *     summary="Update username",
     *     description="Update username for One-Step 2.0",
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
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="User username for user profile update",
     *                 example="john.kiels"
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
     *                 example="Username update was successful"
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
     *                 example="Username update FAILED"
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
    public function updateUsername(Request $request): JsonResponse
    {
      //validate input date
      $input = $this->validate($request,[
        'username'=>'required|string'
      ]);

       try{
           // Check whether user already exist
           $userQuery = User::where(['username'=> $input['username']]);
               
            if($userQuery->exists()){
                
                $user = $userQuery->firstOrFail();

                //Update username
                $user->update([
                    'username'=>$input['username']
                ]);
            
                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Username update was successful."
                ], 400);
            }else{
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        }catch(ModelNotFoundException $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update Username.",
                "data" =>  $e->getMessage()
            ], 400);
        }
      
    }

    /**
     * Update username for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/fullname/update",
     *     summary="Update fullname",
     *     description="Update fullname for One-Step 2.0",
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
     *             @OA\Property(
     *                 property="id",
     *                 type="string",
     *                 description="User ID for user profile update",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *             @OA\Property(
     *                 property="firstname",
     *                 type="string",
     *                 description="User firstname for user profile update",
     *                 example="john"
     *             ),
     *             @OA\Property(
     *                 property="lastname",
     *                 type="string",
     *                 description="User lastname for user profile update",
     *                 example="kiels"
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
     *                 example="Full name update was successful"
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
     *                 example="Full name update FAILED"
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
    public function updateFullname(Request $request): JsonResponse
    {
        //validate input date
        $input = $this->validate($request,[
            'id'=>'required|string',
            'firstname'=>'required|string',
            'lastname'=>'required|string'
        ]);

        try{
           // Check whether user already exist
           $userQuery = User::where(['id'=> $input['id']]);
               
            if($userQuery->exists()){
                
                $user = $userQuery->firstOrFail();

                //Update full name
                $user->update([
                    'first_name'=>$input['firstname'],
                    'last_name'=>$input['lastname']
                ]);
            
                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Full name update was successful."
                ], 400);
            }else{
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        }catch(ModelNotFoundException $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update Full name.",
                "data" =>  $e->getMessage()
            ], 400);
        }
      
    }

    /**
     * Update Country for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/country/update",
     *     summary="Update country",
     *     description="Update country for One-Step 2.0",
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
     *             @OA\Property(
     *                 property="id",
     *                 type="string",
     *                 description="User ID for user profile update",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *             @OA\Property(
     *                 property="country",
     *                 type="string",
     *                 description="User country for user profile update",
     *                 example="United Kindom"
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
     *                 example="Country update was successful"
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
     *                 example="Country update FAILED"
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
    public function updateCountry(Request $request): JsonResponse
    {
      //validate input date
      $input = $this->validate($request,[
        'id'=>'required|string',
        'country'=>'required|string'
      ]);

       try{
           // Check whether user already exist
           $userQuery = User::where(['id'=> $input['id']]);
               
            if($userQuery->exists()){
                
                $user = $userQuery->firstOrFail();

                //Update username
                $user->update([
                    'country'=>$input['country']
                ]);
            
                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Country update was successful."
                ], 400);
            }else{
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        }catch(ModelNotFoundException $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update country.",
                "data" =>  $e->getMessage()
            ], 400);
        }
      
    }
}