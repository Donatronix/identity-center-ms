<?php

namespace App\Api\V1\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Sumra\SDK\JsonApiResponse;

/**
 * Class UserController
 *
 * @package App\Api\V1\Controllers
 */
class UserController extends Controller
{
    /**
     * @param User $model
     */
    private User $model;

    /**
     * UserController constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/admin/users",
     *     summary="Load users list",
     *     description="Load users list",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     x={
     *         "auth-type": "Application & Application User",
     *         "throttling-tier": "Unlimited",
     *         "wso2-application-security": {
     *             "security-types": {"oauth2"},
     *             "optional": "false"
     *         }
     *     },
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit users of page",
     *         @OA\Schema(
     *             type="number"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Count users of page",
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
     *         name="sort[by]",
     *         in="query",
     *         description="Sort by field ()",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sort[order]",
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
     *         response="404",
     *         description="Not found"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            // Get users list
            $users = $this->model::byOwner()->get();

            // Return response
            return response()->jsonApi([
                'type' => 'success',
                'title' => "Users list",
                'message' => 'List of users users successfully received',
                'data' => $users->toArray()
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Users list",
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Save a new user data
     *
     * @OA\Post(
     *     path="/admin/users",
     *     summary="Save a new user data",
     *     description="Save a new user data",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     x={
     *         "auth-type": "Application & Application User",
     *         "throttling-tier": "Unlimited",
     *         "wso2-application-security": {
     *             "security-types": {"oauth2"},
     *             "optional": "false"
     *         }
     *     },
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserPerson")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
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
     */
    public function store(Request $request)
    {
        // Validate input
        $this->validate($request, $this->model::validationRules());

        $user_id = $request->get('contact_id', null);
        try {
            $contact = $this->model::findOrFail($user_id);
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Get contact object",
                'message' => "User with id #{$user_id} not found: " . $e->getMessage(),
                'data' => null
            ], 404);
        }

        // Try to add new user
        try {
            // Create new
            $user = $this->model;
            $user->fill($request->all());
            $user->contact()->associate($contact);
            $user->save();

            // Remove contact object from response
            unset($user->contact);

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'New user registration',
                'message' => "User successfully added",
                'data' => $user->toArray()
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'New user registration',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Get detail info about contact
     *
     * @OA\Get(
     *     path="/admin/users/{id}",
     *     summary="Get detail info about contact",
     *     description="Get detail info about contact",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     x={
     *         "auth-type": "Application & Application User",
     *         "throttling-tier": "Unlimited",
     *         "wso2-application-security": {
     *             "security-types": {"oauth2"},
     *             "optional": "false"
     *         }
     *     },
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Users ID",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Data of contact"
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="User not found",
     *
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="error",
     *                  type="object",
     *                  @OA\Property(
     *                      property="code",
     *                      type="string",
     *                      description="code of error"
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                      description="error message"
     *                  )
     *              )
     *          )
     *     )
     * )
     */
    public function show($id)
    {
        // Get object
        $contact = $this->getObject($id);

        if ($contact instanceof JsonApiResponse) {
            return $contact;
        }

        return response()->jsonApi([
            'type' => 'success',
            'title' => 'User details',
            'message' => "contact details received",
            'data' => $contact->toArray()
        ], 200);
    }

    /**
     * Get user object
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
                'type' => 'danger',
                'title' => "Get user",
                'message' => "User with id #{$id} not found: {$e->getMessage()}",
                'data' => ''
            ], 404);
        }
    }

    /**
     * Update user data
     *
     * @OA\Put(
     *     path="/admin/users/{id}",
     *     summary="Update user data",
     *     description="Update user data",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     x={
     *         "auth-type": "Application & Application User",
     *         "throttling-tier": "Unlimited",
     *         "wso2-application-security": {
     *             "security-types": {"oauth2"},
     *             "optional": "false"
     *         }
     *     },
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User Id",
     *         example="0aa06e6b-35de-3235-b925-b0c43f8f7c75",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserPerson")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        // Validate input
        $this->validate($request, $this->model::validationRules());

        // Read user model
        $user = $this->getObject($id);
        if ($user instanceof JsonApiResponse) {
            return $user;
        }

        // Try update user data
        try {
            // Update data
            $user->fill($request->all());
            $user->save();

            // Remove contact object from response
            unset($user->contact);

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Changing user',
                'message' => "User successfully updated",
                'data' => $user->toArray()
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Change a user',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Delete user from storage
     *
     * @OA\Delete(
     *     path="/admin/users/{id}",
     *     summary="Delete user from storage",
     *     description="Delete user from storage",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     x={
     *         "auth-type": "Application & Application User",
     *         "throttling-tier": "Unlimited",
     *         "wso2-application-security": {
     *             "security-types": {"oauth2"},
     *             "optional": "false"
     *         }
     *     },
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User Id",
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
     *         response="204",
     *         description="Delete shelter"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User not found"
     *     )
     * )
     */
    public function destroy(int $id)
    {
        // Read user model
        $user = $this->getObject($id);
        if ($user instanceof JsonApiResponse) {
            return $user;
        }

        // Try to delete user
        try {
            $user->delete();

            return response()->jsonApi([
                'type' => 'success',
                'title' => "Delete user",
                'message' => 'User is successfully deleted',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Delete of user",
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
