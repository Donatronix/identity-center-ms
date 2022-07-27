<?php

namespace App\Api\V1\Controllers\Application;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TwoFactorSecurity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Validator;

class TwoFASecurityController extends Controller
{

    public function __construct()
    {
        $this->user_id = auth()->user()->id;
        $this->app_name = "ULTAINFINITY WEALTH LAUNCHPAD";
    }

    /**
     * Generate a 2Fa Secret
     *
     * @OA\Get(
     *     path="/2fa/generateSecret",
     *     summary="generate the 2Fa Secret",
     *     description="Generate the 2Fa secret",
     *     tags={"Application | 2FA"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error"
     *     )
     * )
     *
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function generate2faSecret()
    {
        try {

            // Initialise the 2FA class
            $tfa = new \RobThree\Auth\TwoFactorAuth($this->app_name);
            $secret = $tfa->createSecret();
            $qrcode_url = $tfa->getQRCodeImageAsDataUri($this->app_name, $secret);

            // Add the secret key to the user if exist or create new
            $data = TwoFactorSecurity::updateOrCreate(
                ['user_id' => $this->user_id],
                ['secret' => $secret]
            );

            $data['qrcode_url'] = $qrcode_url;

            return response()->jsonApi([
                "type" => "success",
                'title' => 'Generating 2FA secret',
                "message" => "Secret key is generated.",
                "data" => $data->toArray()
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                "type" => "danger",
                'title' => 'Generating 2FA secret',
                "message" => $e->getMessage(),
                "data" => null
            ], 500);
        }
    }

    /**
     * Enable 2Fa security
     *
     * @OA\Post(
     *     path="/2fa/enable2fa",
     *     summary="enable the 2Fa security",
     *     description="enable the 2Fa security",
     *     tags={"Application | 2FA"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *      @OA\RequestBody(
     *            @OA\JsonContent(
     *                type="object",
     *                @OA\Property(
     *                    property="code",
     *                    type="string",
     *                    description="code from authenticator app",
     *                    example="155667"
     *                ),
     *           ),
     *       ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function enable2fa(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        try {
            $tfa = new \RobThree\Auth\TwoFactorAuth($this->app_name);

            $google2fa = TwoFactorSecurity::where('user_id', $this->user_id);
            $secret = $google2fa->value("secret");

            $valid = $tfa->verifyCode($secret, $request->code);

            if ($valid) {
                $google2fa->update(['status' => 1]);
                return response()->jsonApi([
                    'title' => 'Enabling 2FA',
                    "message" => '2FA is enabled successfully',
                ]);
            } else {
                return response()->jsonApi([
                    'title' => 'Enabling 2FA',
                    "message" => 'Invalid verification Code, Please try again.',
                ], 500);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => "Enable 2fa",
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify 2fa code
     *
     * @OA\Post(
     *     path="/2fa/verify",
     *     summary="Verify 2fa code",
     *     description="Verify 2fa code",
     *     tags={"Application | 2FA"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *      @OA\RequestBody(
     *            @OA\JsonContent(
     *                type="object",
     *                @OA\Property(
     *                    property="code",
     *                    type="string",
     *                    description="code from authenticator app",
     *                    example="155667"
     *                ),
     *           ),
     *       ),
     *
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function verify2fa(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        try {
            $tfa = new \RobThree\Auth\TwoFactorAuth($this->app_name);

            $google2fa = TwoFactorSecurity::where('user_id', $this->user_id);
            $secret = $google2fa->value("secret");

            $valid = $tfa->verifyCode($secret, $request->code);

            if ($valid) {
                return response()->jsonApi([
                    'title' => 'Code is valid',
                    "message" => 'Code is valid',
                ]);
            } else {
                return response()->jsonApi([
                    'title' => 'Enabling 2FA',
                    "message" => 'Invalid verification Code, Please try again.',
                ], 500);
            }
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => "verify code",
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Disable 2Fa security
     *
     * @OA\Post(
     *     path="/2fa/disable2fa",
     *     summary="disable the 2Fa security",
     *     description="disable the 2Fa security",
     *     tags={"Application | 2FA"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *      @OA\RequestBody(
     *            @OA\JsonContent(
     *                type="object",
     *                @OA\Property(
     *                    property="password",
     *                    type="string",
     *                    description="current password",
     *                    example="password"
     *                ),
     *           ),
     *       ),
     *
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function disable2fa(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        try {
            $password = User::find($this->user_id)->password;
            if (!(Hash::check($request->get('password'), $password))) {
                // The password doesn't match
                return response()->jsonApi([
                    'title' => 'Disabling 2FA',
                    "message" => 'Your password does not matches with your account password. Please try again.',
                ], 403);
            }

            $google2fa = TwoFactorSecurity::where('user_id', $this->user_id);

            $google2fa->update(['status' => 0]);

            return response()->jsonApi([
                'title' => 'Disabling 2FA',
                "message" => '2FA is now disabled.',
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'Disabling 2FA',
                "message" => $e->getMessage(),
            ], 403);
        }
    }
}
