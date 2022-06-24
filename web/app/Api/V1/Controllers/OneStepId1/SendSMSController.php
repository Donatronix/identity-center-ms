<?php

namespace App\Api\V1\Controllers\OneStepId1;

use App\Api\V1\Controllers\Controller;
use App\Models\TwoFactorAuth;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendSMSController extends Controller
{
    /**
     * Verify phone and send sms
     *
     * @OA\Post(
     *     path="/auth/send-sms",
     *     summary="Verify phone and send sms",
     *     description="Verify phone and send sms",
     *     tags={"OneStep 1.0 | Auth"},
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
     *          response="201",
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
     *          response="400",
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request, $botID = 'twilio'): JsonResponse
    {
        // Validate input data
        $this->validate($request, [
            'phone' => 'required|integer',
        ]);

        try {
            $user = User::where("phone", $request->get('phone', null))->firstOrFail();

            // user already exists
            if ($user->status == User::STATUS_BANNED) {
                return response()->json([
                    "phone_exists" => true,
                    "user_status" => $user->status,
                    "type" => "danger",
                    "message" => "This user has been banned from this platform."
                ], 403);

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

            $sid = $this->sendSms($botID, $request->get('phone', null), $token);

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
                'message' => "Unable to send sms to phone number. Try again",
                "phone_exists" => true
            ], 400);
        }
    }
}
