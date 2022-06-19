<?php

namespace App\Api\V1\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Identification;
use App\Models\User;
use App\Services\IdentityVerification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Sumra\SDK\JsonApiResponse;

class IdentificationController extends Controller
{
    /**
     * Initialize identity verification session
     *
     * @OA\Post(
     *     path="/user-profile/identify",
     *     summary="Initialize identity verification session",
     *     description="Initialize identity verification session",
     *     description="Document type (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
     *     tags={"User Profile"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="document_type",
     *                 type="string",
     *                 description="Document type (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
     *                 enum={"1", "2", "3", "4"},
     *                 example="1"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Identity verification session successfully initialized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function identifyStart(Request $request): mixed
    {
        // Get user
        $user = User::find(Auth::user()->id);

        if (!$user) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Get user",
                'message' => "User with id #{$user->id} not found!",
                'data' => '',
            ], 404);
        }

//         Get object
//        $user = $this->getObject(Auth::user()->getAuthIdentifier());
//
//        if ($user instanceof JsonApiResponse) {
//            return $user;
//        }

        // Init verify session
        $data = (new IdentityVerification())->startSession($user, $request);

        // Return response to client
        if ($data->status === 'success') {
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Start KYC verification',
                'message' => "Session started successfully",
                'data' => $data->verification
            ], 200);
        } else {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Start KYC verification',
                'message' => $data->message,
                'data' => [
                    'code' => $data->code ?? ''
                ]
            ], 400);
        }
    }

    /**
     * Webhook to handle Veriff response
     *
     * @OA\Post(
     *     path="/user-profile/identify-webhook",
     *     summary="Webhook to handle Veriff response",
     *     description="Webhook to handle Veriff response",
     *     tags={"User Profile"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserIdentify")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="User identity verified successfully"
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="User created"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function identifyWebHook(Request $request): mixed
    {
        // Validate input
        try {
            $this->validate($request, User::identifyValidationRules());
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'User data identification',
                'message' => "Validation error",
                'data' => $e->getMessage(),
            ], 400);
        }

        // Try to save received document data
        try {
            // Find existing user
            $user = User::find(Auth::user()->id);
            if (!$user) {
                return response()->jsonApi([
                    'type' => 'danger',
                    'title' => "Get user",
                    'message' => "User with id #" . Auth::user()->id . " not found!",
                    'data' => '',
                ], 404);
            }

            // Transform data and save
            $identifyData = $request->all();
            foreach ($identifyData['document'] as $key => $value) {
                $identifyData['document_' . $key] = $value;
            }
            unset($identifyData['document']);

            $user->fill($identifyData);
            $user->status = User::STATUS_ACTIVE;
            $user->save();

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'New user registration',
                'message' => "User identity verified successfully",
                'data' => [],
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'User data identification',
                'message' => "Unknown error",
                'data' => $e->getMessage(),
            ], 500);
        }
    }
}