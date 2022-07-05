<?php

namespace App\Api\V1\Controllers\Application;

use App\Api\V1\Controllers\Controller;
use App\Traits\TokenHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use TokenHandler;

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
