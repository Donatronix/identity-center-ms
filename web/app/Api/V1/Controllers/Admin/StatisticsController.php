<?php

namespace App\Api\V1\Controllers\Admin;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PubSub;
use Spatie\Permission\Models\Role;
use Sumra\SDK\JsonApiResponse;
use Throwable;

/**
 * Class UserController
 *
 * @package App\Api\V1\Controllers\Admin
 */
class StatisticsController extends Controller
{
    /**
     * Display total number of users
     *
     * @OA\Get(
     *     path="/admin/users/count/all",
     *     summary="Count all users",
     *     description="Get the count of all users in the system",
     *     tags={"Admin | Users Statistics"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Response(
     *         response="200",
     *         description="Data retrieved",
     *         @OA\JsonContent(ref="#/components/schemas/OkResponse")
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error",
     *         @OA\JsonContent(ref="#/components/schemas/WarningResponse")
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not Found",
     *         @OA\JsonContent(ref="#/components/schemas/DangerResponse")
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function totalUsers(): JsonResponse
    {
        try {
            $userCount = User::whereIn('status', [0,1,2])->count();

            // Return response
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Users Count',
                'message' => 'Total count of users retrieved successfully',
                'data' => $userCount
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Users Count',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Display total number of new users
     *
     * @OA\Get(
     *     path="/admin/users/count/new",
     *     summary="Count all new users",
     *     description="Get the count of all new users in the system",
     *     tags={"Admin | Users Statistics"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Response(
     *         response="200",
     *         description="Data retrieved",
     *         @OA\JsonContent(ref="#/components/schemas/OkResponse")
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error",
     *         @OA\JsonContent(ref="#/components/schemas/WarningResponse")
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not Found",
     *         @OA\JsonContent(ref="#/components/schemas/DangerResponse")
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/DangerResponse")
     *     )
     * )
     *
     * @param mixed $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function totalNewUsers():JsonResponse
    {
        try {
            $userCount = User::whereIn('status', [0,1,2])
                              ->whereMonth('created_at', date('m'))
                              ->count();

            // Return response
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Users Count',
                'message' => 'Total count of users retrieved successfully',
                'data' => $userCount
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Users Count',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

}
