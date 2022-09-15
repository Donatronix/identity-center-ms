<?php

namespace App\Api\V1\Controllers\Admin;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * @return JsonResponse
     */
    public function totalUsers(): JsonResponse
    {
        try {
            $userCount = User::whereIn('status', [0, 1, 2])->count();

            if (!empty($userCount) && $userCount != null) {
                // Return response
                return response()->jsonApi([
                    'title' => 'Users Count',
                    'message' => 'Total count of users retrieved successfully',
                    'data' => $userCount,
                ]);
            }

            // Return response
            return response()->jsonApi([
                'title' => 'Users Count',
                'message' => 'Total count of users NOT found.'
            ], 404);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'Users Count',
                'message' => $e->getMessage()
            ], 500);
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
     * @return JsonResponse
     */
    public function totalNewUsers(): JsonResponse
    {
        try {
            $userCount = User::whereIn('status', [0, 1, 2])
                ->whereMonth('created_at', date('m'))
                ->count();

            if (!empty($userCount) && $userCount != null) {
                return response()->jsonApi([
                    'title' => 'Users Count',
                    'message' => 'Total count of users retrieved successfully',
                    'data' => $userCount,
                ]);
            }

            // Return response
            return response()->jsonApi([
                'title' => 'Users Count',
                'message' => 'Total count of users NOT found.'
            ], 404);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'Users Count',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display total number of new users
     *
     * @OA\Get(
     *     path="/admin/users/count/status",
     *     summary="Count all new users",
     *     description="Get the status count of all users in the system",
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
     * @return JsonResponse
     */
    public function getUsersStatus(Request $request)
    {
        try {
            $data = [];

            $data['banned'] = User::query()->where('status', User::STATUS_BANNED)->count();
            $data['active'] = User::query()->where('status', User::STATUS_ACTIVE)->count();

            $data['inactive'] = User::query()->where('status', User::STATUS_INACTIVE)->count();

            // Return response
            return response()->jsonApi([
                'title' => 'Users Status Statistics',
                'message' => 'Status of users retrieved successfully',
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'title' => 'Users Status Statistics',
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }

    /**
     * Get number of registered users per day, week, month, year, day of week, or hour
     *
     * @OA\Get(
     *     path="/admin/users/count/registration",
     *     summary="Get number of registered users",
     *     description="Get number of registered users per day, week, month, year, day of week, or hour",
     *     tags={"Admin | Partners"},
     *
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Query filter: day,week,month,year,day of week,hour",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *
     *             @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  description="partner's id",
     *                  example="1"
     *              ),
     *              @OA\Property(
     *                  property="staff",
     *                  type="number",
     *                  description="staff information",
     *                  example="staff object"
     *              ),
     *              @OA\Property(
     *                  property="mobile",
     *                  type="string",
     *                  description="partner phone",
     *                  example="09087384756"
     *              ),
     *                @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="partner email",
     *                  example="partner@gmail.com"
     *              ),
     *              @OA\Property(
     *                  property="status",
     *                  type="boolean",
     *                  description="partner's status, 1:active, 0:blocked",
     *                  example="1"
     *              ),
     *              @OA\Property(
     *                  property="category",
     *                  type="string",
     *                  description="partner's category",
     *                  example=""
     *              ),
     *              @OA\Property(
     *                  property="country",
     *                  type="string",
     *                  description="partner's country",
     *                  example="Nigeria"
     *              ),
     *              @OA\Property(
     *                  property="first_name",
     *                  type="string",
     *                  description="First Name of Partner",
     *                  example="Sola"
     *              ),
     *              @OA\Property(
     *                  property="signature",
     *                  type="string",
     *                  description="partner signature",
     *                  example="base64 in string"
     *              ),
     *              @OA\Property(
     *                  property="last_name",
     *                  type="string",
     *                  description="Last name of partner",
     *                  example="Sola"
     *              ),
     *              @OA\Property(
     *                  property="full_address",
     *                  type="string",
     *                  description="full address of partner",
     *                  example="877 Noemy Alley Suite 981 Lake Rainamouth, AL 61994"
     *              ),
     *              @OA\Property(
     *                  property="apartment_details",
     *                  type="string",
     *                  description="apartment address of partner",
     *                  example="877 Noemy Alley Suite 981 Lake Rainamouth, AL 61994"
     *              ),
     *              @OA\Property(
     *                  property="commercial_address",
     *                  type="string",
     *                  description="commercial address of partner",
     *                  example="877 Noemy Alley Suite 981 Lake Rainamouth, AL 61994"
     *              ),
     *              @OA\Property(
     *                  property="city",
     *                  type="string",
     *                  description="city of partner",
     *                  example="Los Angeles"
     *              ),
     *              @OA\Property(
     *                  property="postcode",
     *                  type="string",
     *                  description="postcode of partner",
     *                  example="22002"
     *              ),
     *              @OA\Property(
     *                  property="created_at",
     *                  type="string",
     *                  description="timestamp of data entry",
     *                  example="2022-05-09T12:45:46.000000Z"
     *              ),
     *              ),
     *          ),
     *     ),
     *
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not Found"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getUserRegistrationStatistics(Request $request): mixed
    {
        try {
            $this->validate($request, [
                'filter' => 'sometimes|required|string|in:day,week,month,year,day of week,hour',
            ]);

            $filter = $request->get('filter', 'date');

            $data = match ($filter) {
                'day' => $this->getUsersByDay(),
                'week' => $this->getUsersByWeek(),
                'month' => $this->getUsersByMonth(),
                'year' => $this->getUsersByYear(),
                'day of week' => $this->getUsersByDayOfWeek(),
                'hour' => $this->getUsersByHour(),
                default => $this->getUsersByDate(),
            };

            return response()->jsonApi([
                'title' => 'Users Status Statistics',
                'message' => 'Status of users retrieved successfully',
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'title' => 'Users Registration Statistics',
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }

    /**
     * @param string $format
     *
     * @return Builder[]|Collection
     */
    protected function getUsersByDate(string $format = '%Y-%m-%d'): Collection|array
    {
        return $this->groupFilter($format, 'date');
    }

    /**
     * @param string $format
     *
     * @return Builder[]|Collection
     */
    protected function getUsersByMonth(string $format = '%Y-%m'): Collection|array
    {
        return $this->groupFilter($format, 'month');
    }

    /**
     * @param string $format
     *
     * @return Builder[]|Collection
     */
    protected function getUsersByYear(string $format = '%Y'): Collection|array
    {
        return $this->groupFilter($format, 'year');
    }

    /**
     * @param string $format
     *
     * @return Builder[]|Collection
     */
    protected function getUsersByWeek(string $format = '%W'): Collection|array
    {
        return $this->groupFilter($format, 'week');
    }

    /**
     * @param string $format
     *
     * @return Builder[]|Collection
     */
    protected function getUsersByDay(string $format = '%d'): Collection|array
    {
        return $this->groupFilter($format, 'day');
    }

    /**
     * @param string $format
     *
     * @return Builder[]|Collection
     */
    protected function getUsersByDayOfWeek(string $format = '%l'): Collection|array
    {
        return $this->groupFilter($format, 'day_of_week');
    }

    /**
     * @param string $format
     *
     * @return Builder[]|Collection
     */
    protected function getUsersByHour(string $format = '%H'): Collection|array
    {
        return $this->groupFilter($format, 'hour');
    }

    /**
     * @param string $format
     * @param string $type
     *
     * @return Builder|Collection
     */
    protected function groupFilter(string $format, string $type): Collection|array
    {
        $type = Str::snake($type);

        return User::query()
            ->whereIn('status', [0, 1, 2])
            ->select(
                "id",
                DB::raw("(count(id)) as total_users"),
                DB::raw("(DATE_FORMAT(created_at, $format)) as $type")
            )
            ->orderBy('created_at')
            ->groupBy(DB::raw("DATE_FORMAT(created_at, $format)"))
            ->get();
    }
}
