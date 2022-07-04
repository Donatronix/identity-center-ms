<?php

namespace App\Api\V1\Controllers\Public\OneStepId1;

use App\Api\V1\Controllers\Controller;
use App\Exceptions\SMSGatewayException;
use App\Models\TwoFactorAuth;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PhoneVerifyController extends Controller
{
    /**
     * Verify phone and send sms
     *
     * @OA\Post(
     *     path="/auth/send-phone",
     *     summary="Verify phone and send sms",
     *     description="Verify phone and send sms",
     *     tags={"OneStep 1.0 | Auth"},
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
     * @throws ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Validate input data
            $this->validate($request, [
                'phone_number' => 'required|numeric|min:10',
            ]);

            // Get User by phone number
            $user = User::where("phone", $request->get('phone_number', null))
                ->firstOrFail();

            $message = 'This phone number exists';
            $phone_exist = True;

            // user already exists
            if ($user->status == User::STATUS_BANNED) {
                return response()->jsonApi([
                    "type" => "danger",
                    "phone_exists" => true,
                    "user_status" => $user->status,
                    "message" => "This user has been banned from this platform."
                ], 403);

            } elseif ($user->status == User::STATUS_INACTIVE) {
                return response()->jsonApi([
                    "code" => 200,
                    "message" => "This user already exists. Required send verification code",
                    "phone_exists" => true,
                    "user_status" => $user->status,
                    "type" => "success",
                ], 200);
            } elseif ($user->status == User::STATUS_ACTIVE) {
                return response()->jsonApi([
                    "code" => 200,
                    "message" => "This user already exists.",
                    "phone_exists" => true,
                    "user_status" => $user->status,
                    "type" => "success",
                ], 200);
            }

        } catch (ValidationException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Send phone',
                'message' => "Validation error: " . $e->getMessage(),
                'data' => null
            ], 400);
        } catch (ModelNotFoundException $e) {
            //pass
            //New user
        }

        DB::beginTransaction();
        // user does  not exist
        try {
            $token = TwoFactorAuth::generateToken();

            $botID = 'twilio';

            $sid = $this->sendSms($botID, $request->get('phone', null), $token);

            $user = User::create([
                "phone" => $request->get('phone', null),
                "status" => User::STATUS_INACTIVE,
            ]);

            $twoFa = TwoFactorAuth::create([
                "sid" => $sid,
                "user_id" => $user->id,
                "code" => $token,
            ]);
            // Send the code to the user
            DB::commit();

            // Return response
            return response()->jsonApi([
                'type' => 'success',
                'title' => "Create new user. Step 1",
                'message' => 'User was successful created',
                'sid' => $sid,
                // TODO Remove this before shipping
                "test_purpose_token" => $token,
            ], 201);
        } catch (Exception $e) {
            if ($e instanceof SMSGatewayException) {
                return response()->jsonApi([
                    'type' => 'danger',
                    'title' => "Create new user. Step 1",
                    'message' => "Unable to send sms to phone Number.",
                ], 400);
            } else {
                DB::rollBack();

                return response()->jsonApi([
                    'type' => 'danger',
                    'title' => "Create new user. Step 1",
                    'message' => "Unable to create user.",
                ], 400);
            }
        }
    }
}
