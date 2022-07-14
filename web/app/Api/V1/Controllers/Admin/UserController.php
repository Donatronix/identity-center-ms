<?php

namespace App\Api\V1\Controllers\Admin;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
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
use Auth;

/**
 * Class UserController
 *
 * @package App\Api\V1\Controllers\Admin
 */
class UserController extends Controller
{
    /**
     * Display a listing of the users
     *
     * @OA\Get(
     *     path="/admin/users",
     *     summary="Get all users list in system",
     *     description="Get all users list in system",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "default": {
     *             "AdminRead",
     *             "AdminWrite",
     *             "ManagerRead",
     *             "ManagerWrite"
     *         }
     *     }},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Category of Users to get: Admin, Super, Investor",
     *         @OA\Schema(
     *             type="All"
     *         )
     *     ),
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
     *         description="Success send data",
     *
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User parameter list",
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     description="User uuid",
     *                     example="9443407b-7eb8-4f21-8a5c-9614b4ec1bf9",
     *                 ),
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                     description="first_name",
     *                     example="Vasya",
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                     description="last_name",
     *                     example="Vasya",
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     description="Username",
     *                     example="Vasya",
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="User phone number",
     *                     example="2348065302534",
     *                 ),
     *                 @OA\Property(
     *                     property="birthday",
     *                     type="string",
     *                     description="User birthday",
     *                     example="2348065302534",
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     description="User status",
     *                     example="1",
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
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
    public function index(Request $request): mixed
    {
        try {
            /**
             * User Category OR Role
             *
             */
            $type = $request->type;
            if (!$type || strtolower($type) == 'all') {
                $users = User::paginate($request->get('limit', config('settings.pagination_limit')));
            }
            else {

                /**
                 *
                 */
                if (!in_array(ucfirst($request->type), User::$types)) {
                    throw new \Exception("User Type not allowed", 400);
                }
                Role::firstOrCreate(['name' => $request->type]);

                $users = User::role($type)
                    ->paginate($request->get('limit', config('settings.pagination_limit')));
            }

            // Return response
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Users list',
                'message' => 'List of users successfully received',
                'data' => $users->toArray()
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Users list',
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
     *         "passport": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="first_name",
     *                 type="string",
     *                 description="First name for new user account",
     *                 required={"true"},
     *                 example="John"
     *             ),
     *             @OA\Property(
     *                 property="last_name",
     *                 type="string",
     *                 description="Last name for new user account",
     *                 required={"true"},
     *                 example="Kiels"
     *             ),
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="Username for new user account",
     *                 required={"true"},
     *                 example="johnkiels"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="Email for user account",
     *                 required={"true"},
     *                 example="johnkiels@ultainfinity"
     *             ),
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Phone number for user account",
     *                 required={"true"},
     *                 example="+4492838989"
     *             ),
     *             @OA\Property(
     *                 property="birthday",
     *                 type="string",
     *                 description="Birthday for user account",
     *                 required={"true"},
     *                 example="2002-04-09"
     *             ),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 description="Gender for user account",
     *                 required={"true"},
     *                 example="m"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 description="Status for user account",
     *                 example="0"
     *             ),
     *             @OA\Property(
     *                 property="subscribed_to_announcement",
     *                 type="string",
     *                 description="subscribed to announcement",
     *                 required={"true"},
     *                  example=""
     *             ),
     *             @OA\Property(
     *                 property="address_line1",
     *                 type="string",
     *                 description="Address line 1 for user account",
     *                 required={"true"},
     *                 example="45 kingston Street"
     *             ),
     *             @OA\Property(
     *                 property="address_line2",
     *                 type="string",
     *                 description="Address line 2 for user account",
     *                 required={"true"},
     *                 example="Radek Layout"
     *             ),
     *             @OA\Property(
     *                 property="address_city",
     *                 type="string",
     *                 description="Address_city for user account",
     *                 required={"true"},
     *                 example="Westbron"
     *             ),
     *             @OA\Property(
     *                 property="address_country",
     *                 type="string",
     *                 description="Address country for user account",
     *                 required={"true"},
     *                 example="UK"
     *             ),
     *             @OA\Property(
     *                 property="address_zip",
     *                 type="string",
     *                 description="Address zip for user account",
     *                 required={"true"},
     *                 example="+235"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 description="Password for user account",
     *                 required={"true"},
     *                 example="xxxxxxx"
     *             ),
     *             @OA\Property(
     *                 property="accept_terms",
     *                 type="boolean",
     *                 description="Accept terms for user account",
     *                 required={"true"},
     *                 example="true"
     *             )
     *         )
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
     *         description="Not Found"
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
     *
     * @param Request $request
     *
     * @return User|JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): User|JsonResponse
    {
        // Try to add new user
        try {
            $validate = Validator::make($request->all(), User::adminValidationRules());

            //Validation response
            if ($validate->fails()) {
                return response()->jsonApi([
                    'type' => 'danger',
                    'title' => 'New user registration',
                    'message' => $validate->errors(),
                    'data' => null
                ], 400);
            }

            //Get validated input
            $validated = $validate->validated();

            //User data
            $input = array_merge($validated, [
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'status' => User::STATUS_ACTIVE,
                'verify_token' => Str::random(32),
            ]);

            $user = User::create($input);

            PubSub::transaction(function () {
            })->publish('sendVerificationEmail', [
                'email' => $user->email,
                'display_name' => $user->display_name,
                'verify_token' => $user->verify_token,
            ], 'mail');

            PubSub::transaction(function () {
            })->publish('NewUserRegisteredListener', [
                'user' => $user->toArray(),
            ], 'new-user-registered');

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Create admin user account',
                'message' => "New user registered successfully!",
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
     * Get detail info about user
     *
     * @OA\Get(
     *     path="/admin/users/{id}",
     *     summary="Get detail info about user",
     *     description="Get detail info about user",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User Id",
     *         example="96b47d3c-8197-4965-811b-74d04247d4f9",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Data of user"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     description="code of error"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     description="error message"
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function show(string $id): mixed
    {
        try {
            $user = User::where('id', $id)->firstOrFail();

            return response()->jsonApi([
                'type' => 'success',
                'title' => 'User details',
                'message' => "user details received",
                'data' => $user->toArray()
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "User details",
                'message' => "Unable to receive user details",
                'data' => null,
            ], 404);
        }
    }

    /**
     * Update user data
     *
     * @OA\Put(
     *     path="/admin/users/update/{id}",
     *     summary="Update user data",
     *     description="Update user data",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         example="96b47d3c-8197-4965-811b-74d04247d4f9",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="first_name",
     *                 type="string",
     *                 description="First name for new user account",
     *                 required={"true"},
     *                 example="John"
     *             ),
     *             @OA\Property(
     *                 property="last_name",
     *                 type="string",
     *                 description="Last name for new user account",
     *                 required={"true"},
     *                 example="Kiels"
     *             ),
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="Username for new user account",
     *                 required={"true"},
     *                 example="johnkiels"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="Email for user account",
     *                 required={"true"},
     *                 example="johnkiels@ultainfinity"
     *             ),
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="Phone number for user account",
     *                 required={"true"},
     *                 example="+4492838989"
     *             ),
     *             @OA\Property(
     *                 property="birthday",
     *                 type="string",
     *                 description="Birthday for user account",
     *                 required={"true"},
     *                 example="2002-04-09"
     *             ),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 description="Gender for user account",
     *                 required={"true"},
     *                 example="m"
     *             ),
     *             @OA\Property(
     *                 property="address_line1",
     *                 type="string",
     *                 description="Address line 1 for user account",
     *                 required={"true"},
     *                 example="45 kingston Street"
     *             ),
     *             @OA\Property(
     *                 property="address_line2",
     *                 type="string",
     *                 description="Address line 2 for user account",
     *                 required={"true"},
     *                 example="Radek Layout"
     *             ),
     *             @OA\Property(
     *                 property="address_city",
     *                 type="string",
     *                 description="Address_city for user account",
     *                 required={"true"},
     *                 example="Westbron"
     *             ),
     *             @OA\Property(
     *                 property="address_country",
     *                 type="string",
     *                 description="Address country for user account",
     *                 required={"true"},
     *                 example="UK"
     *             ),
     *             @OA\Property(
     *                 property="address_zip",
     *                 type="string",
     *                 description="Address zip for user account",
     *                 required={"true"},
     *                 example="+235"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     )
     * )
     *
     * @param Request $request
     * @param string $id
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, string $id): JsonApiResponse
    {
        $validate = Validator::make($request->all(), User::adminUpdateValidateRules());

        //Validation response
        if ($validate->fails()) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Admin user update',
                'message' => $validate->errors(),
                'data' => null
            ], 400);
        }

        try {
            //Get validated input
            $validated = $validate->validated();

            $userQuery = User::where('id', $id);

            if ($userQuery->exists()) {
                //Update user
                $user = $userQuery->update($validated);

                //Send response
                return response()->jsonApi([
                    'type' => 'success',
                    'title' => 'Admin user update',
                    'message' => "User successfully updated",
                    'data' => $user
                ], 200);
            }

            // Return response to client
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Admin user update',
                'message' => "User does NOT exist.",
                'data' => null
            ], 404);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Admin user update',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Delete user from database
     *
     * @OA\Delete(
     *     path="/admin/users/{id}",
     *     summary="Delete user from database",
     *     description="Delete user from database",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
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
     *
     *     @OA\Response(
     *         response="200",
     *         description="User was delete successfully"
     *     ),
     *     @OA\Response(
     *         response="204",
     *         description="Delete shelter"
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     )
     * )
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function destroy(mixed $id): mixed
    {
        // Read user model
        $user = $this->getObject($id);
        if ($user instanceof JsonApiResponse) {
            return $user;
        }

        // Try to delete user
        try {
            $user = User::findOrFail($id);
            $user->delete();

            $users = User::paginate(config('settings.pagination_limit'));

            return response()->jsonApi([
                'type' => 'success',
                'title' => "Delete of user",
                'message' => 'User is successfully deleted',
                'data' => $users->toArray(),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Delete failed",
                'message' => "User does not exist",
                'data' => null,
            ], 404);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Delete of user",
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
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
            return User::findOrFail($id);
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
     * Verify User
     *
     * @OA\Post(
     *     path="/admin/users/verify",
     *     summary="Create new user",
     *     description="Create new user",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *         name="token",
     *         required=true,
     *         in="query",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function verify(Request $request): mixed
    {
        try {
            DB::transaction(function () use ($request) {
                $validator = Validator::make($request->all(), [
                    'token' => ['required'],
                ]);

                if ($validator->fails()) {
                    throw new Exception($validator->messages()->first());
                }
                $select = DB::table('password_resets')
                    ->where('token', $request->token);

                if ($select->get()->isEmpty()) {
                    throw new Exception('Invalid verification token');
                }

                $user = User::where('email', $select->first()->email)->first();
                $user->email_verified_at = Carbon::now()->getTimestamp();
                $user->save();

                DB::table('password_resets')
                    ->where('token', $request->token)
                    ->delete();
            });
        } catch (Throwable $th) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Verification failed",
                'message' => $th->getMessage(),
                'data' => null
            ], 404);
        }

        return response()->jsonApi([
            'type' => 'success',
            'title' => "Verification successful",
            'message' => "Email is verified",
            'data' => null
        ], 200);
    }

    /**
     * Send verification email
     *
     * @OA\Post(
     *     path="/admin/users/verify/send",
     *     summary="Create new user",
     *     description="Create new user",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *         name="email",
     *         required=true,
     *         in="query",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function verifyEmail(Request $request): mixed
    {
        try {
            DB::transaction(function () use ($request) {
                $validator = Validator::make($request->all(), [
                    'email' => ['required', 'string', 'email', 'max:255'],
                ]);

                if ($validator->fails()) {
                    throw new Exception($validator->messages()->first());
                }

                $validated = $validator->validated();

                $user = User::where('email', $validated['email'])->first();

                $verify = DB::table('password_resets')->where([
                    'email' => $validated['email'],
                ]);

                if ($verify->exists()) {
                    $verify->delete();
                }

                $token = Str::random(60);
                $password_reset = DB::table('password_resets')->insert([
                    'email' => $validated['email'],
                    'token' => $token,
                    'created_at' => Carbon::now(),
                ]);

                if ($password_reset) {
                    PubSub::transaction(function () {

                    })->publish('sendVerificationEmail', [
                        'email' => $user->email,
                        'display_name' => $user->display_name,
                        'verify_token' => $user->verify_token,
                    ], 'mail');
                }
            });
        } catch (Throwable $th) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Verification failed",
                'message' => $th->getMessage(),
                'data' => null
            ], 404);
        }
        return response()->jsonApi([
            'type' => 'success',
            'title' => "Verification email",
            'message' => "A verification mail has been sent",
            'data' => null
        ], 200);
    }

    /**
     * Admin adding of User
     *
     * @OA\Post(
     *     path="/admin/users/add",
     *     description="Add a new User",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="first_name",
     *                 type="string",
     *                 description="First name for new user account",
     *                 required={"true"},
     *                 example="John"
     *             ),
     *             @OA\Property(
     *                 property="last_name",
     *                 type="string",
     *                 description="Last name for new user account",
     *                 required={"false"},
     *                 example="Kiels"
     *             ),
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="Username for new user account",
     *                 required={"true"},
     *                 example="johnkiels"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="Email for user account",
     *                 required={"true"},
     *                 example="johnkiels@ultainfinity"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 description="Default Password",
     *                 required={"true"},
     *                 example="password"
     *             ),
     *             @OA\Property(
     *                 property="user_type",
     *                 type="string",
     *                 description="User Type: Investor, Admin, Super",
     *                 required={"true"},
     *                 example="Super"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addUser(Request $request)
    {
        try {
            $this->validate($request, [
                'first_name' => 'required|string',
                'password' => 'required|string|min:6',
                'email' => 'required|email|unique:users,email',
                'username' => 'required|string|unique:users,username',
                'user_type' => 'required|in:Investor,Admin,Super'
            ]);

            //
            $input = $request->all();

            /**
             * Get Role
             *
             */
            $role = Role::firstOrCreate([
                'name' => $input['user_type']
            ]);
            unset($input['user_type']);

            /**
             * Store
             *
             */
            $input['verify_token'] = Str::random(32);
            $input['password'] = Hash::make($input['password']);

            $user = User::create($input);
            $user->roles()->sync($role->id);

            /**
             * Notify
             */
            PubSub::publish('sendVerificationEmail', [
                'email' => $user->email,
                'display_name' => $user->display_name,
                'verify_token' => $user->verify_token,
            ], 'mail');

            PubSub::publish('NewUserRegisteredListener', [
                'user' => $user->toArray(),
            ], 'new-user-registered');

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Add user account',
                'message' => "New user registered successfully!",
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            if (isset($user))
                $user->delete();

            return response()->json([
                'type' => 'danger',
                'title' => 'Add user account',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Admin Details of Users
     *
     * @OA\Post(
     *     path="/admin/users/details",
     *     description="Get details of users",
     *     tags={"Admin | Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 description="Array of user IDs",
     *                 required={"true"},
     *                 @OA\Items()
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Details fetched"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function usersDetails(Request $request)
    {
        try {
            $this->validate($request, [
                'users' => 'required|array',
            ]);

            foreach ($request->users as $key => $user) {
                $user = User::find($user);
                if ($user) {
                    $users[] = $user;
                }
            }

            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Users Details',
                'message' => "Information fetched successfully!",
                'data' => $users
            ], 200);
        }
        catch (Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => 'Users Details',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
