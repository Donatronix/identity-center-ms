<?php

namespace App\Api\V1\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;
use PubSub;
use Throwable;

class ServiceAdminController extends Controller
{

    /**
     *  Add new admin
     *
     * @OA\Post(
     *     path="/admin/service/admins",
     *     description="Add new admin",
     *     tags={"Admin | Microservice Admins"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     x={
     *          "auth-type": "Application & Application Use",
     *          "throttling-tier": "Unlimited",
     *          "wso2-appliocation-security": {
     *              "security-types": {"oauth2"},
     *              "optional": "false"
     *           },
     *     },
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="User id of admin",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Admin role",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="service",
     *         in="query",
     *         description="Microservice Admin",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Output data",
     *
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Admin parameter list",
     *                 @OA\Property(
     *                     property="user_id",
     *                     type="string",
     *                     description="Admin uuid",
     *                     example="9443407b-7eb8-4f21-8a5c-9614b4ec1bf9",
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     description="Admin role",
     *                     example="admin",
     *                 ),
     *                 @OA\Property(
     *                     property="service",
     *                     type="string",
     *                     description="Microservice",
     *                     example="waiting-lists-ms",
     *                 ),
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *          response="401",
     *          description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="user_id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="role",
     *                  type="string",
     *                  description="Role not found"
     *              ),
     *          ),
     *     ),
     *
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     ),
     * )
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request): mixed
    {
        try {
            $admin = null;
            DB::transaction(function () use ($request, &$admin) {
                $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string|exists:users,id',
                    'role' => 'required|string|exists:roles,name',
                    'service' => 'required|string',
                ]);

                if ($validator->fails()) {
                    return response()->jsonApi([
                        'type' => 'danger',
                        'title' => "Invalid data",
                        'message' => $validator->messages()->toArray(),
                        'data' => null,
                    ], 404);
                }

                // Retrieve the validated input...
                $validated = $validator->validated();

                $admin = User::find($validated['user_id']);
                if (empty($admin)) {
                    throw new Exception('User does not exist');
                }


                PubSub::transaction(function () use ($validated, &$admin) {
                    $admin = User::find($validated['user_id']);
                })->publish('AdminManagerEvent', [
                    'admin' => $admin,
                    'role' => $validated['role'],
                    'service' => $validated['service'],
                    'action' => 'store',
                ], 'service_admin');

            });
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Not operation",
                'message' => "Admin was not added. Please try again.",
                'data' => null,
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Operation failed",
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
        return response()->jsonApi([
            'type' => 'success',
            'title' => 'Operation was a success',
            'message' => 'Admin role was updated successfully',
            'data' => $admin->toArray(),
        ], 200);
    }


    /**
     *  Update admin role
     *
     * @OA\Patch(
     *     path="/admin/service/admins",
     *     description="Update admin",
     *     tags={"Admin | Microservice Admins"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     x={
     *          "auth-type": "Application & Application Use",
     *          "throttling-tier": "Unlimited",
     *          "wso2-appliocation-security": {
     *              "security-types": {"oauth2"},
     *              "optional": "false"
     *           },
     *     },
     *
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Admin role",
     *         @OA\Schema(
     *             type="string"
     *         ),
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Admin user id",
     *         @OA\Schema(
     *             type="string"
     *         ),
     *     ),     *
     *     @OA\Parameter(
     *         name="service",
     *         in="query",
     *         description="Microservice Admin",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Output data",
     *
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Admin parameter list",
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     description="Admin uuid",
     *                     example="9443407b-7eb8-4f21-8a5c-9614b4ec1bf9",
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Name",
     *                     example="Vasya",
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     description="Admin email",
     *                     example="sumra chat",
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Admin phone number",
     *                     example="+445667474124146",
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     description="Admin role",
     *                     example="admin",
     *                 ),
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *          response="401",
     *          description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="user_id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="role",
     *                  type="string",
     *                  description="Role not found"
     *              ),
     *          ),
     *     ),
     *
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     ),
     * )
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function update(Request $request): mixed
    {
        try {

            DB::transaction(function () use ($request) {
                $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string|exists:users,id',
                    'role' => 'required|string',
                    'service' => 'required|string',
                ]);

                if ($validator->fails()) {
                    return response()->jsonApi([
                        'type' => 'danger',
                        'title' => "Not operation",
                        'message' => $validator->messages()->toArray(),
                        'data' => null,
                    ], 404);
                }

                // Retrieve the validated input...
                $validated = $validator->validated();

                $admin = User::find($validated['user_id']);
                if (empty($admin)) {
                    throw new Exception('User does not exist');
                }


                PubSub::transaction(function () {

                })->publish('AdminManagerEvent', [
                    'admin' => $admin,
                    'role' => $validated['role'],
                    'service' => $validated['service'],
                    'action' => 'update',
                ], 'service_admin');
            });
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Update failed",
                'message' => "Admin does not exist",
                'data' => null,
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Update failed",
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
        return response()->jsonApi([
            'type' => 'success',
            'title' => 'Update was a success',
            'message' => 'Admin was updated successfully',
            'data' => User::find($request->user_id),
        ], 200);
    }


    /**
     *  Remove admin
     *
     * @OA\Delete(
     *     path="/admin/service/admins",
     *     description="Remove admin",
     *     tags={"Admin | Microservice Admins"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     x={
     *          "auth-type": "Application & Application Use",
     *          "throttling-tier": "Unlimited",
     *          "wso2-appliocation-security": {
     *              "security-types": {"oauth2"},
     *              "optional": "false"
     *           },
     *     },
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Admin user id",
     *         @OA\Schema(
     *             type="string"
     *         ),
     *     ),     *
     *     @OA\Parameter(
     *         name="service",
     *         in="query",
     *         description="Microservice Admin",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Output data",
     *
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Admin parameter list",
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     description="Admin uuid",
     *                     example="9443407b-7eb8-4f21-8a5c-9614b4ec1bf9",
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Name",
     *                     example="Vasya",
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     description="Admin email",
     *                     example="sumra chat",
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Admin phone number",
     *                     example="+445667474124146",
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     description="Admin role",
     *                     example="admin",
     *                 ),
     *             ),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *          response="401",
     *          description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="user_id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="role",
     *                  type="string",
     *                  description="Role not found"
     *              ),
     *          ),
     *     ),
     *
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     ),
     * )
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function destroy(Request $request): mixed
    {
        try {

            DB::transaction(function () use ($request) {
                $validator = Validator::make($request->all(), [
                    'user_id' => 'required|string|exists:users,id',
                    'service' => 'required|string',
                ]);

                if ($validator->fails()) {
                    return response()->jsonApi([
                        'type' => 'danger',
                        'title' => "Not operation",
                        'message' => $validator->messages()->toArray(),
                        'data' => null,
                    ], 404);
                }

                // Retrieve the validated input...
                $validated = $validator->validated();

                $admin = User::find($validated['user_id']);
                if (empty($admin)) {
                    throw new Exception('User does not exist');
                }


                PubSub::transaction(function () {

                })->publish('AdminManagerEvent', [
                    'admin' => $admin,
                    'service' => $validated['service'],
                    'action' => 'delete',
                ], 'service_admin');
            });
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Update failed",
                'message' => "Admin does not exist",
                'data' => null,
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Update failed",
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
        return response()->jsonApi([
            'type' => 'success',
            'title' => 'Update was a success',
            'message' => 'Admin was updated successfully',
            'data' => User::find($request->user_id),
        ], 200);
    }


}
