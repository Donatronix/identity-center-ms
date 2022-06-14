<?php

namespace App\Api\V1\Controllers\OneStepId2;

use App\Api\V1\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;

class SocialMediaController extends Controller
{
    /**
     * Connect user to social media for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-profile/redirect",
     *     summary="Create provider redirect URL",
     *     description="Create social media provider redirect URL for One-Step 2.0",
     *     tags={"Connect User to Social Media by OneStep 2.0"},
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
     *                 description="User ID for social media connection",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *             @OA\Property(
     *                 property="provider",
     *                 type="string",
     *                 description="User social media provider.",
     *                 required={"true"},
     *                  example="facebook"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function createRedirectUrl(Request $request): JsonResponse
    {
      //validate input date
      $input = $this->validate($request, [
                    'id'=>'required|string',
                    'provider'=>'required|string'
                ]);

       try{
           // Check whether user already exist
           $userExist = User::where(['id'=> $input['id']])->exists();
               
            if($userExist){
               
                //Generate redirect url
                $redirectUrl = Socialite::driver($input['provider'])->redirect();

                if(!empty($redirectUrl) && $redirectUrl!=null){
                     //Show response
                    return response()->json([
                        'type' => 'success',
                        'message' => "Redirect URL created successfully.",
                        "data" => ['redirect_url'=>$redirectUrl]
                    ], 200);
                }else{
                    return response()->json([
                        'type' => 'danger',
                        'message' => "Redirect URL was NOT created.",
                        "data" => null
                    ], 400);
                }

            }else{
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does not exist.",
                    "data" => null
                ], 400);
            }
        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to create redirect URL. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
      
    }

    /**
     * Connect user to social media for One-Step 2.0
     *
     * @OA\Get(
     *     path="/user-profile/{provider}/callback",
     *     summary="Process provider callback",
     *     description="Process social media provider callback for One-Step 2.0",
     *     tags={"Connect User to Social Media by OneStep 2.0"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *          name="provider",
     *          in="path",
     *          description="Callback provider info",
     *          required=true,
     *          example="facebook",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function mediaCallback(Request $request): JsonResponse
    {
      //validate input date
      $input = $this->validate($request, ['provider'=>'required|string']);

       try{
           //Get user info
           $user = Socialite::driver($input['provider'])->stateless()->user();
        
           
               
            if($userExist){
                //Save user record
                $userExist = User::whereEmail($user->email)->doesntExist();

                if(!empty($redirectUrl) && $redirectUrl!=null){
                     //Show response
                    return response()->json([
                        'type' => 'success',
                        'message' => "Redirect URL created successfully.",
                        "data" => ['redirect_url'=>$redirectUrl]
                    ], 200);
                }else{
                    return response()->json([
                        'type' => 'danger',
                        'message' => "Redirect URL was NOT created.",
                        "data" => null
                    ], 400);
                }

            }else{
                //Response with required info
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does not exist.",
                    "data" => null
                ], 400);
            }
        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to create redirect URL. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
      
    }
}