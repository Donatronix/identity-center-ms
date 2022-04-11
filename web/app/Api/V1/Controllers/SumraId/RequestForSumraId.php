<?php

namespace App\Api\V1\Controllers\OneStepId;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\TwoFactorAuth;
use App\Api\V1\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RequestForSumraId extends Controller
{  
    
 
  
     /**
     * Request for sumra id 
     *
     * @OA\Post(
     *     path="/auth/send-code",
     *     summary="Request for sumra id ",
     *     description="Request for sumra id ",
     *     tags={"One-Step Users"},
     *
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"auth_code_from_user"},
     *
     *             @OA\Property(
     *                 property="auth_code_from_user",
     *                 type="string",
     *                 description="verification code enter by user",
     *                 example="ksdaofdf"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response=200,
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
     *                 property="sid",
     *                 type="string",
     *                 example="Create new user. Step 1"
     *             ),
     *             @OA\Property(
     *                 property="validate_auth_code",
     *                 type="boolean",
     *                 example="true"
     *                 description="Indicates if validation is successful"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User was successful created"
     *             ),
     *             @OA\Property(
     *                 property="user_status",
     *                 type="number",
     *                 description="User Status INACTIVE = 0, ACTIVE = 1, BANNED = 2",

     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=400,
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
     *                 property="validate_auth_code",
     *                 type="boolean",
     *                 example="false"
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
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        // ...
        // Validate input data
        $this->validate($request, [
            "first_name" => "required",
            "last_name" => "required",
            'email' => "required",
            'birthday' => "required",
            'password' => "required"
        ]);
}