<?php

namespace App\Api\V1\Controllers\Public\OneStepId2;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminTokenController extends Controller
{
    /**
     * Verify Admin Access Token for One-Step 2.0
     *
     * @OA\Post(
     *     path="/user-account/v2/verify-access-token",
     *     summary="Verify admin access token",
     *     description="Verify admin access token for One-Step 2.0",
     *     tags={"OneStep 2.0 | Admin Access Token"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="Admin username",
     *                 example="ihor"
     *             ),
     *             @OA\Property(
     *                 property="access_code",
     *                 type="string",
     *                 description="Admin access code verification",
     *                 example="RJK78S"
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
     *            @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 example="Admin access code verification"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Access code verified successfully"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *                 example=""
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
     *                 example="Admin access code verification"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Access code NOT verified"
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
        //validate input data
        $validator = Validator::make($request->all(), [
            'access_code' => ['required', 'string'],
            'username' => 'required|string|exists:users,username'
        ]);

        if ($validator->fails()) {
            return response()->jsonApi([
                'title' => "Admin access code verification.",
                'message' => "Input validator errors. Try again.",
                "data" => $validator->errors()
            ], 422);
        }

        try {
            //Get validated data
            $input = $validator->validated();

            // Check whether user already exist
            $tokenQuery = User::where([
                'access_code' => $input['access_code'],
                'username' => $input['username']
            ]);

            if ($tokenQuery->exists()) {
                $data['user'] = $tokenQuery->first();
                $data['token'] = $tokenQuery->createToken($input['username'])->accessToken;

                //Show response
                return response()->jsonApi([
                    'title' => "Admin access code verification.",
                    'message' => "Access code verified successfully",
                    "data" => $data
                ]);
            }

            return response()->jsonApi([
                'title' => "Admin access code verification.",
                'message' => "Access code NOT verified."
            ], 400);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => "Admin access code verification.",
                'message' => "Unable to verify access code. Try again.",
                "data" => $e->getMessage()
            ], 400);
        }
    }
}
