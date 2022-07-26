<?php

namespace App\Api\V1\Controllers\Application;

use App\Api\V1\Controllers\Controller;
use App\Models\MediaConnect;
use App\Models\User;
use App\Services\FetchWhatsAppInfo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;

class SocialMediaController extends Controller
{
    /**
     * Connect user to social media for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-profile/redirect",
     *     summary="Create provider redirect URL",
     *     description="Create social media provider redirect URL",
     *     tags={"Application | Social Media Connect"},
     *
     *     security={{ "bearerAuth": {} }},
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
     *          response="200",
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response="400",
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
        $input = $this->validate($request, ['provider' => 'required|string']);

        try {
            // Check whether user already exist
            $userExist = User::where(['id' => Auth::user()->id])->exists();

            if ($userExist) {

                //Generate redirect url
                $redirectUrl = Socialite::driver($input['provider'])->redirect();

                if (!empty($redirectUrl) && $redirectUrl != null) {
                    //Show response
                    return response()->jsonApi([
                        'type' => 'success',
                        'message' => "Redirect URL created successfully.",
                        "data" => ['redirect_url' => $redirectUrl]
                    ], 200);
                } else {
                    return response()->jsonApi([
                        'type' => 'danger',
                        'message' => "Redirect URL was NOT created.",
                        "data" => null
                    ], 400);
                }

            } else {
                return response()->jsonApi([
                    'type' => 'danger',
                    'message' => "User profile does not exist.",
                    "data" => null
                ], 400);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
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
     *     description="Process social media provider callback",
     *     tags={"Application | Social Media Connect"},
     *
     *     security={{ "bearerAuth": {} }},
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
     *          response="200",
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response="400",
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
        try {
            //Get user info
            $mediaUser = Socialite::driver($provider)->stateless()->user();

            if (!empty($mediaUser) && $mediaUser != null) {
                //Check whether media connection already exist
                $mediaQuery = MediaConnect::where(['email' => $mediaUser->email, 'provider' => $input['provider']]);

                $mediaArray = [
                    'user_id' => Auth::user()->id,
                    'media_id' => $mediaUser->id,
                    'provider' => $provider,
                    'name' => $mediaUser->name,
                    'email' => $mediaUser->email,
                    'phone' => Auth::user()->phone
                ];

                if ($mediaQuery->doesntExist()) {
                    //Save user record
                    MediaConnect::create($mediaArray);
                }

                //Show response
                return response()->jsonApi([
                    'type' => 'success',
                    'message' => "{$provider} connection was successful.",
                    "data" => $mediaArray
                ], 200);

            } else {
                //Response with required info
                return response()->jsonApi([
                    'type' => 'danger',
                    'message' => "Unable to connect to {$provider}.",
                    "data" => null
                ], 400);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'message' => "Unable to connect to {$provider}. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Connect user to whatsapp social media
     *
     * @OA\Get(
     *     path="/user-profile/whatsapp/connect",
     *     summary="Connect user to WhatsApp",
     *     description="Connect user to WhatsApp social media",
     *     tags={"Application | Social Media Connect"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *     )
     * )
     *
     * @param FetchWhatsAppInfo $connchat
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function whatsappConnect(FetchWhatsAppInfo $connchat): JsonResponse
    {
        $provider = 'whatsapp';

        try {
            //Get user info
            $phoneNumber = Auth::user()->phone;

            //send test chat
            $userConn = $connchat->sendTestChat($phoneNumber);

            if ($userConn) {
                //Check whether media connection already exist
                $mediaQuery = MediaConnect::where(['email' => Auth::user()->email, 'provider' => $provider]);

                $mediaArray = [
                    'user_id' => Auth::user()->id,
                    'provider' => $provider,
                    'name' => Auth::user()->username,
                    'email' => Auth::user()->email,
                    'phone' => $phoneNumber
                ];

                if ($mediaQuery->doesntExist()) {
                    //Save user record
                    MediaConnect::create($mediaArray);
                }

                //Show response
                return response()->jsonApi([
                    'type' => 'success',
                    'message' => "{$provider} connection was successful.",
                    "data" => $mediaArray
                ], 200);

            } else {
                //Response with required info
                return response()->jsonApi([
                    'type' => 'danger',
                    'message' => "Unable to connect to {$provider}.",
                    "data" => null
                ], 400);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'message' => "Unable to connect to {$provider}. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Connect user to social media
     *
     * @OA\Get(
     *     path="/user-profile/social/connections",
     *     summary="Retrieve social media connections",
     *     description="Retrieve social media connections",
     *     tags={"Application | Social Media Connect"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response="400",
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
        try {
            //Fetch social media connections
            $userMedia = MediaConnect::where(['user_id' => Auth::user()->id])->get();

            if (!empty($userMedia) && $userMedia != null) {
                //Show response
                return response()->jsonApi([
                    'type' => 'success',
                    'message' => "Retrieved media connections successfully.",
                    "data" => $userMedia->toArray()
                ], 200);

            } else {
                //Response with required info
                return response()->jsonApi([
                    'type' => 'danger',
                    'message' => "No media connection found.",
                    "data" => null
                ], 404);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'message' => "Unable to retrieved media connections. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
    }
}
