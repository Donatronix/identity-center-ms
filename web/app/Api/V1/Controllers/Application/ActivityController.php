<?php

namespace App\Api\V1\Controllers\Application;

use App\Api\V1\Controllers\Controller;
use App\Models\Activity;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Sumra\SDK\Services\JsonApiResponse;

class ActivityController extends Controller
{
    /**
     * @param Activity $model
     */
    private Activity $model;

    public function __construct(Activity $model)
    {
        $this->model = $model;
        $this->user_id = auth()->user()->id;
    }

    /**
     * Getting user activities
     *
     * @OA\Get(
     *     path="/activities",
     *     summary="Getting activity detail",
     *     description="Getting activity detail",
     *     tags={"Application | User Activities"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit",
     *         @OA\Schema(
     *             type="number"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Count",
     *         @OA\Schema(
     *             type="number"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search keywords",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sort-by",
     *         in="query",
     *         description="Sort by field ()",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sort-order",
     *         in="query",
     *         description="Sort order (asc, desc)",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success send data"
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
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            // Get activities
            $activities = $this->model
                ->where("user_id", $this->user_id)
                ->orderBy($request->get('sort-by', 'created_at'), $request->get('sort-order', 'desc'))
                ->paginate($request->get('limit', config('settings.pagination_limit')));

            // Return response
            return response()->jsonApi([
                'title' => "Activities list",
                'message' => 'List of activities successfully received',
                'data' => $activities->toArray()
            ]);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => "Activities list",
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Save a new activity
     *
     * @OA\Post(
     *     path="/activities",
     *     summary="Save a new activity",
     *     description="Save a new activity",
     *     tags={"Application | User Activities"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Activity")
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Successfully created"
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
     *         response="403",
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            "title" => "required|string",
            "description" => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        // transform the request object to add date
        $request->merge([
            'activity_time' => Carbon::now(),
            "user_id" => $this->user_id
        ]);

        // Try to add new activity
        try {
            // Create new
            $activity = $this->model->create($request->all());

            // Return response
            return response()->jsonApi([
                'title' => 'New activity registration',
                'message' => "Activity successfully added",
                'data' => $activity->toArray()
            ]);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'New activity registration',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get activity object
     *
     * @param $id
     * @return mixed
     */
    private function getObject($id): mixed
    {
        try {
            return $this->model::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => "Get activity",
                'message' => "Activity with id #{$id} not found: {$e->getMessage()}",
            ], 404);
        }
    }

    /**
     * Delete activity from storage
     *
     * @OA\Delete(
     *     path="/activities/{id}",
     *     summary="Delete activity",
     *     description="Delete activity",
     *     tags={"Application | User Activities"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity Id",
     *         example="0aa06e6b-35de-3235-b925-b0c43f8f7c75",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successfully delete"
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
     */
    public function destroy($id)
    {
        // Read activity model
        $activity = $this->getObject($id);
        if ($activity instanceof JsonApiResponse) {
            return $activity;
        }

        // Try to delete activity
        try {
            $activity->delete();

            return response()->jsonApi([
                'title' => "Deleted activity",
                'message' => 'activity is successfully deleted'
            ]);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => "Delete of activity",
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
