<?php

namespace App\Api\V1\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Sumra\SDK\Services\AdminManager;
use Throwable;

class ServiceAdminController extends Controller
{
    /**
     *  Display a listing of the administrators
     *
     * @OA\Get(
     *     path="/admin/administrators",
     *     description="Get all administrators",
     *     tags={"Admin | Administrators"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Administrator role",
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
     *                 description="Administrator parameter list",
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     description="Administrator uuid",
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
     *                     description="Administrator email",
     *                     example="admin@mail.com",
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Administrator phone number",
     *                     example="++44625546453",
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     description="Administrator role",
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
     *         response="400",
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name not found"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email not found"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone not found"
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
    public function index(Request $request): mixed
    {
        try {
            $this->validate($request, [
                'role' => 'nullable|string|in:admin,super admin',
            ]);

            $query = User::with('roles')->role(['admin', 'super admin']);
            if ($request->has('role')) {
                $query = User::with('roles')->whereHas('roles', function ($query) use ($request) {
                    $query->where('name', $request->role);
                });
            }

            $administrators = $query->paginate($request->get('limit', config('settings.pagination_limit')));

            return response()->jsonApi([
                'title' => 'Operation was success',
                'message' => 'The data was displayed successfully',
                'data' => $administrators
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => "Not operation",
                'message' => "Error showing all users",
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'title' => "Operation failed",
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     *  Add new admin
     *
     * @OA\Post(
     *     path="/admin/administrators",
     *     description="Add new admin",
     *     tags={"Admin | Administrators"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Administrator id",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="The admin role",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="service",
     *         in="query",
     *         description="The service to add admin to",
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
     *                 description="Administrator parameter list",
     *             ),
     *         ),
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
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name not found"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email not found"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone not found"
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
            $validator = Validator::make($request->all(), [
                'service' => 'nullable|string|min:3|max:50',
                'role' => 'required|string|min:3|max:50',
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->jsonApi([
                    'title' => "Invalid request",
                    'message' => $validator->errors()->first(),
                ], 400);
            }

            $user = User::findOrFail($request->get('user_id'));

            $user->assignRole($request->get('role'));

            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Operation was a success',
                'message' => 'Administrator was added successfully',
                'data' => null,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Not operation",
                'message' => "Administrator was not added. Please try again.",
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
    }

    /**
     *  Update admin record
     *
     * @OA\Put(
     *     path="/admin/administrators/{id}",
     *     description="Update admin",
     *     tags={"Admin | Administrators"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     @OA\Parameter(
     *         name="service",
     *         in="query",
     *         description="Administrator service",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Administrator role",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Administrator user id",
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
     *                 description="Administrator parameter list",
     *             ),
     *         ),
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
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name not found"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email not found"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone not found"
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
     * @param         $id
     *
     * @return mixed
     */
    public function update(Request $request, $id): mixed
    {
        try {

            $validator = Validator::make($request->all(), [
                'service' => 'nullable|string|min:3|max:50',
                'role' => 'required|string|min:3|max:50',
            ]);

            if ($validator->fails()) {
                return response()->jsonApi([
                    'title' => "Invalid request",
                    'message' => $validator->errors()->first(),
                ], 400);
            }

            $user = User::query()->findOrFail($id);

            $user->assignRole($request->get('role'));

            return response()->jsonApi([
                'title' => 'Update was a success',
                'message' => 'Administrator was updated successfully',
            ]);


        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => "Update failed",
                'message' => "Administrator does not exist",
                'data' => null,
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'title' => "Update failed",
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
    }

    /**
     *  Delete admin record
     *
     * @OA\Delete(
     *     path="/admin/administrators/{id}",
     *     description="Delete admin",
     *     tags={"Admin | Administrators"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Administrator user id",
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
     *                 description="Administrator parameter list",
     *             ),
     *         ),
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
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name not found"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email not found"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone not found"
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
     * @param $id
     *
     * @return mixed
     */
    public function destroy($id): mixed
    {
        try {

            $user = User::findOrFail($id);
            foreach ($user->getRoleNames() as $role) {
                $user->removeRole($role);
            }

            return response()->jsonApi([
                'title' => 'Operation was a success',
                'message' => 'Administrator was deleted successfully',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => "Delete failed",
                'message' => "Administrator does not exist",
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'title' => "Delete failed",
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     *  Update admin role
     *
     * @OA\Patch(
     *     path="/admin/administrators/{id}",
     *     description="Update admin role",
     *     tags={"Admin | Administrators"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Administrator role",
     *         @OA\Schema(
     *             type="string"
     *         ),
     *     ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Administrator user id",
     *         @OA\Schema(
     *             type="string"
     *         ),
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
     *                 description="Administrator parameter list",
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     description="Administrator uuid",
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
     *                     description="Administrator email",
     *                     example="sumra chat",
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Administrator phone number",
     *                     example="+445667474124146",
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     description="Administrator role",
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
     *         response="400",
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name not found"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email not found"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone not found"
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
     * @param         $id
     *
     * @return mixed
     */
    public function updateRole(Request $request, $id): mixed
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|string',
                'service' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->jsonApi([
                    'title' => "Not operation",
                    'message' => $validator->errors()->first(),
                ], 404);
            }

            $admin = new AdminManager();
            $admin->removeAdmin(array_merge($request->all(), [
                'user_id' => $id,
            ]));


            return response()->jsonApi([
                'title' => 'Role update was a success',
                'message' => 'Administrator was updated successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => "Update failed",
                'message' => "Administrator does not exist",
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Update failed",
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
    }

    /**
     *  Remove admin role
     *
     * @OA\Patch(
     *     path="/admin/administrators/remove/{id}",
     *     description="Update admin role",
     *     tags={"Admin | Administrators"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Administrator role",
     *         @OA\Schema(
     *             type="string"
     *         ),
     *     ),
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Administrator user id",
     *         @OA\Schema(
     *             type="string"
     *         ),
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
     *                 description="Administrator parameter list",
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     description="Administrator uuid",
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
     *                     description="Administrator email",
     *                     example="sumra chat",
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Administrator phone number",
     *                     example="+445667474124146",
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     description="Administrator role",
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
     *         response="400",
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="id",
     *                  type="string",
     *                  description="Uuid admin not found"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name not found"
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email not found"
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone not found"
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
     * @param         $id
     *
     * @return mixed
     */
    public function removeRole(Request $request, $id): mixed
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|string',
                'service' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->jsonApi([
                    'title' => "Not operation",
                    'message' => $validator->errors()->first(),
                ], 404);
            }

            User::findOrFail($id)->removeRole($request->role);


            return response()->jsonApi([
                'title' => 'Role removal was a success',
                'message' => 'Administrator was updated successfully',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => "Role removal failed",
                'message' => "User does not exist",
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'title' => "Role removal failed",
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     *  Display a listing of services
     *
     * @OA\Get(
     *     path="/admin/administrators/services",
     *     description="Get all services",
     *     tags={"Admin | Administrators"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "Admin",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Administrator role",
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
     *                 description="Administrator parameter list",
     *             ),
     *         ),
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
     *          @OA\JsonContent(
     *              type="object",
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
    public function getServices(Request $request): mixed
    {
        try {

            $admin = new AdminManager();

            return response()->jsonApi([
                'title' => 'Operation was success',
                'message' => 'The data was displayed successfully',
                'data' => $admin->getServices(),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'title' => "Not operation",
                'message' => "Error showing all transactions",
            ], 404);
        } catch (Throwable $e) {
            return response()->jsonApi([
                'title' => "Operation failed",
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
