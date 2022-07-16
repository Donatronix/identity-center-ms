<?php

namespace App\Api\V1\Controllers\Application;

use App\Http\Controllers\Controller;
use App\Models\KYC;
use App\Models\User;
use App\Services\IdentityVerification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Class KYCController
 *
 * @package App\Api\V1\Controllers\User
 */
class KYCController extends Controller
{
    /**
     * Initialize identity verification session
     *
     * @OA\Post(
     *     path="/user-identity/start",
     *     summary="Initialize identity verification session",
     *     description="Initialize identity verification session",
     *     description="Document type (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
     *     tags={"User | KYC"},
     *
     *     security={{ "bearerAuth": {} }},
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
     *         description="Not Found"
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
     * Upload documents for users KYC
     *
     * @OA\Post(
     *     path="/user-identify/upload",
     *     summary="Upload documents for users KYC",
     *     description="Upload documents for users KYC",
     *     tags={"User | KYC"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserKYC")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="KYC submitted",
     *         @OA\JsonContent(ref="#/components/schemas/OkResponse")
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Validator Error",
     *         @OA\JsonContent(ref="#/components/schemas/WarningResponse")
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/DangerResponse")
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): mixed
    {
        // Validate input
        try {
            $this->validate($request, KYC::validationRules());
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'User KYC identification',
                'message' => "Validation error: " . $e->getMessage(),
                'data' => null
            ], 400);
        }

        // Try to save received document data
        try {
            // Find existing user
            // $user = User::find(Auth::user()->id);
            // if (!$user) {
            //     return response()->jsonApi([
            //         'type' => 'danger',
            //         'title' => "Get user",
            //         'message' => "User with id #" . Auth::user()->id . " not found!",
            //         'data' => '',
            //     ], 404);
            // }

//            // Find exist user
//            $user = $this->getObject(Auth::user()->getAuthIdentifier());
//            if ($user instanceof JsonApiResponse) {
//                return $user;
//            }

            // Transform data and save
            // $identifyData = $request->all();
            // foreach ($identifyData['document'] as $key => $value) {
            //     $identifyData['document_' . $key] = $value;
            // }
            // unset($identifyData['document']);

            // $user->fill($identifyData);
            // $user->status = User::STATUS_ACTIVE;
            // // $user->status = User::STATUS_STEP_3;
            // $user->save();
            // Return response to client

            /**
             * Save KYC info
             *
             */
            $data = $request->all();
            $data['user_id'] = Auth::user()->id;

            $kyc = KYC::create($data);

            return response()->jsonApi([
                'type' => 'success',
                'title' => 'User KYC identification',
                'message' => "User identity submitted successfully",
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'User KYC identification',
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}