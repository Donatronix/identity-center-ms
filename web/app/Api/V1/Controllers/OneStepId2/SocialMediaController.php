<?php

namespace App\Api\V1\Controllers\OneStepId2;

use App\Api\V1\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MediaConnect;
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
      $input = $this->validate($request, ['provider'=>'required|string']);

       try{
           // Check whether user already exist
           $userExist = User::where(['id'=> Auth::user()->id])->exists();
               
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
     * @param string $provider
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function mediaCallback(string $provider): JsonResponse
    {
       try{
            //Get user info
            $mediaUser = Socialite::driver($provider)->stateless()->user();
           
            if(!empty($mediaUser) && $mediaUser!=null){
                //Check whether media connection already exist
                $mediaQuery = MediaConnect::where(['email'=>$mediaUser->email, 'provider'=>$input['provider']]);
                
                $mediaArray = [
                    'user_id'=>Auth::user()->id,
                    'media_id'=>$mediaUser->id,
                    'provider'=>$provider,
                    'name'=>$mediaUser->name,
                    'email'=>$mediaUser->email,
                    'phone'=>Auth::user()->phone
                ];
                
                if($mediaQuery->doesntExist()){
                    //Save user record
                    MediaConnect::create($mediaArray);
                }
                
                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "{$provider} connection was successful.",
                    "data" => $mediaArray
                ], 200);

            }else{
                //Response with required info
                return response()->json([
                    'type' => 'danger',
                    'message' => "Unable to connect to {$provider}.",
                    "data" => null
                ], 400);
            }
        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to connect to {$provider}. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
      
    }

    /**
     * Connect user to social media for One-Step 2.0
     *
     * @OA\Get(
     *     path="/user-profile/social/connections",
     *     summary="Retrieve social media connections",
     *     description="Retrieve social media connections for One-Step 2.0",
     *     tags={"Connect User to Social Media by OneStep 2.0"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
     *
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
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getMediaData(): JsonResponse
    {
        try{
            //Fetch social media connections
            $userMedia = MediaConnect::where(['user_id'=>Auth::user()->id])->get();

            if(!empty($userMedia) && $userMedia!=null){
                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Retrieved media connections successfully.",
                    "data" => $userMedia->toArray()
                ], 200);

            }else{
                //Response with required info
                return response()->json([
                    'type' => 'danger',
                    'message' => "No media connection found.",
                    "data" => null
                ], 404);
            }
        }catch(Exception $e){
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to retrieved media connections. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
      
    }
}