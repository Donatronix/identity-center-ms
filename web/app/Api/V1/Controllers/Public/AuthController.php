<?php

namespace App\Api\V1\Controllers\Public;

use App\Api\V1\Controllers\Controller;
use App\Traits\TokenHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\VerifyStepInfo;
use App\Services\SendVerifyToken;

class AuthController extends Controller
{
    use TokenHandler;

    /**
     * Login user endpoint
     *
     * @OA\Post(
     *     path="/sign-in",
     *     description="User login",
     *     tags={"Auth"},
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
     *                 property="username",
     *                 type="string",
     *                 description="Username",
     *                 required={"true"},
     *                 example="john.kiels"
     *             ),
     *             @OA\Property(
     *                 property="channel",
     *                 type="string",
     *                 description="OTP type or channel (SMS or Messenger).",
     *                 required={"true"},
     *                  example="sms"
     *             ),
     *             @OA\Property(
     *                 property="handler",
     *                 type="string",
     *                 description="Account verification handler.",
     *                 required={"true"},
     *                 example="@ultainfinity"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *      ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function login(Request $request)
    {
        try {

            /**
             * Validate
             *
             */
            $this->validate($request, [
                'channel' => 'required',
                'handler' => 'string',
                'username' => 'required|exists:users,username'
            ]);

            $input = $request->all();

            /**
             * Get User
             *
             */
            $user = User::where('username', $request->username)->first();

            // Create verification token (OTP)
            $otpToken = VerifyStepInfo::generateOTP(7);
            $validity = VerifyStepInfo::tokenValidity(30);

            $sendto = $user->phone;
            if ($input['channel'] != 'sms') {
                $sendto = $input['handler'];
            }

            VerifyStepInfo::create([
                'username' => $input['username'],
                'channel' => $input['channel'],
                'receiver' => $sendto,
                'code' => $otpToken,
                'validity' => $validity
            ]);

            $sendOTP = new SendVerifyToken();
            try {
                $sendOTP->dispatchOTP($input['channel'], $sendto, $otpToken);
            }
            catch (\Throwable $th) {}

            // For Testing
            if (app()->environment('local', 'staging')) {
                $data = ['code' => $otpToken];
            }

            //Show response
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Login',
                'message' => "{$input['channel']} verification code sent to {$sendto}.",
                "data" => $data ?? null
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => 'Login',
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    /**
     * Login user endpoint
     *
     * @OA\Post(
     *     path="/sign-in/otp",
     *     description="User login",
     *     tags={"Auth"},
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
     *                 property="username",
     *                 type="string",
     *                 description="Username",
     *                 required={"true"},
     *                 example="john.kiels"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 description="OTP code",
     *                 required={"true"},
     *                 example="12345"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *      ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function verifyOTP(Request $request)
    {
        try {

             /**
             * Validate
             *
             */
            $this->validate($request, [
                'code' => 'required|string',
                'username' => 'required|exists:users,username'
            ]);

            $input = $request->all();

            //
            $model = VerifyStepInfo::where(['code' => $input['code']])
                ->where('username', $input['username'])->first();

            if (!$model) {
                return response()->jsonApi([
                    'type' => 'warning',
                    'title' => 'Login',
                    'message' => "Username OR Token not correct",
                ], 400);
            }

            /**
             * Create Auth Token
             *
             */
            $user = User::where('username', $input['username'])->first();
            $token = $user->createToken($user->username)->accessToken;
            $data = [
                'user' => $user,
                'accessToken' => $token
            ];

            /**
             * Cleanup
             *
             */
            $model->delete();

            //
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Login',
                'message' => "Operation succeeded",
                'data' => $data
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => 'Login',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     *
     * @OA\Post(
     *     path="/auth/refresh-token",
     *     summary="Refresh Token",
     *     description="Refresh expired Token",
     *     tags={"Auth"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Refresh Token",
     *                 example="def502009171ac97fa3d2487..."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response="200",
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
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Token Refreshed"
     *             ),
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Token Object"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="400", description="Bad Request")
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function refresh(Request $request): JsonResponse
    {
        // Validate input data
        $this->validate($request, [
            'token' => 'required',
        ]);

        try {
            $token = $this->refreshToken($request->token);

            return response()->jsonApi([
                "type" => "success",
                "message" => "Token Refresh",
                "data" => $token
            ], 400);
        } catch (Exception $e) {
            return response()->jsonApi([
                "type" => "danger",
                "message" => $e->getMessage(),
            ], 400);
        }
    }
}
