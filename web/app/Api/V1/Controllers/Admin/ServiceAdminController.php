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
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *              @OA\Property(
     *                 property="role",
     *                 type="string",
     *                 description="Admin role",
     *                 required={"true"},
     *                 example="admin"
     *             ),
     *              @OA\Property(
     *                 property="service",
     *                 type="string",
     *                 description="Microservice Admin",
     *                 example="keiland"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="Admin email",
     *                 example="kiels@ultainfinity.com"
     *             ),
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Admin phone number",
     *                 example="+448494840383"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Output data",
     *     ),
     *
     *     @OA\Response(
     *          response="401",
     *          description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *     ),
     *
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
    public function store(Request $request): mixed
    {
        try {
            $admin = null;

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|exists:roles,name',
                'service' => 'required|string',
                'phone' => 'required|string',
                'email' => 'required|string|email',
            ]);

            if ($validator->fails()) {
                return response()->jsonApi([
                    'type' => 'danger',
                    'title' => "Add new admin",
                    'message' => $validator->messages()->toArray(),
                    'data' => null,
                ], 404);
            }

            // Retrieve the validated input...
            $input = $validator->validated();

            $adminQuery = User::where('email', $input['email']);

            if ($adminQuery->doesntExist()) {
                //Save new admin
                User::create($input);

                //Send message
                PubSub::transaction(function () use ($adminQuery, $input, &$admin) {
                    $admin = $adminQuery->first();
                })->publish('AdminManagerEvent', [
                    'admin' => $admin,
                    'role' => $input['role'],
                    'service' => $input['service'],
                    'action' => 'store',
                ], 'service_admin');

                $respData = $adminQuery->first();

                return response()->jsonApi([
                    'type' => 'success',
                    'title' => 'Add new admin',
                    'message' => 'Admin role was updated successfully',
                    'data' => [
                        'user_id' => $respData['id'],
                        'role' => $respData['role'],
                        'service' => $respData['service']
                    ]
                ], 200);
            }

            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Add new admin",
                'message' => "Admin already exist. Please try again.",
                'data' => null,
            ], 400);

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Add new admin",
                'message' => "Admin was not added. Please try again.",
                'data' => null,
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Add new admin",
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }

    }

    /**
     *  Update admin role
     *
     * @OA\Patch(
     *     path="/admin/service/admins",
     *     description="Update admin",
     *     tags={"Admin | Microservice Admins"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *              @OA\Property(
     *                 property="user_id",
     *                 type="string",
     *                 description="Admin user ID",
     *                 required={"true"},
     *                 example="admin9443407b-7eb8-4f21-8a5c-9614b4ec1bf9"
     *             ),
     *             @OA\Property(
     *                 property="role",
     *                 type="string",
     *                 description="Admin role",
     *                 required={"true"},
     *                 example="admin"
     *             ),
     *              @OA\Property(
     *                 property="service",
     *                 type="string",
     *                 description="Microservice Admin",
     *                 example="keiland"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="Admin email",
     *                 example="kiels@ultainfinity.com"
     *             ),
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Admin phone number",
     *                 example="+448494840383"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Output data",
     *     ),
     *
     *     @OA\Response(
     *          response="401",
     *          description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found"
     *     ),
     *
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
                        'title' => "Admin user update",
                        'message' => $validator->errors(),
                        'data' => null,
                    ], 404);
                }

                // Retrieve the validated input...
                $validated = $validator->validated();

                $adminQuery = User::where('id', $validated['user_id']);

                if ($adminQuery->exists()) {
                    //Fetch user
                    $admin = $adminQuery->first();

                    //Update user
                    // $adminQuery->update([
                    //     'role' => $validated['role'],
                    //     'service' => $validated['service'],
                    // ]);

                    //send message
                    PubSub::publish('AdminManagerEvent', [
                            'admin' => $admin,
                            'role' => $validated['role'],
                            'service' => $validated['service'],
                            'action' => 'update',
                        ], 'service_admin');

                    return response()->jsonApi([
                        'type' => 'success',
                        'title' => 'Admin user update',
                        'message' => 'Admin user updated successfully',
                        'data' => null,
                    ], 200);
                }

                return response()->jsonApi([
                    'type' => 'success',
                    'title' => 'Admin user update',
                    'message' => 'Admin user does NOT exist',
                    'data' => null,
                ], 404);

            });

        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Admin user update",
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }

    }

    /**
     *  Remove admin
     *
     * @OA\Delete(
     *     path="/admin/service/admins",
     *     description="Remove admin",
     *     tags={"Admin | Microservice Admins"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Admin user id",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
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
     *     ),
     *
     *     @OA\Response(
     *          response="401",
     *          description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found"
     *     ),
     *
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


                PubSub::publish('AdminManagerEvent', [
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
