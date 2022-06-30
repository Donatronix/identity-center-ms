<?php

namespace App\Api\V1\Controllers\Admin;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PubSub;
use Sumra\SDK\JsonApiResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

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
     *
     *     x={
     *         "auth-type": "Application & Application User",
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
     *                 ),
     *             ),
     *         ),
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
            // Get users list
            $users = User::paginate($request->get('limit', config('settings.pagination_limit')));

            // Return response
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Users list',
                'message' => 'List of users users successfully received',
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
     *     x={
     *         "auth-type": "Application & Application User",
     *         "wso2-application-security": {
     *             "security-types": {"oauth2"},
     *             "optional": "false"
     *         }
     *     },
     *
     *     @OA\Parameter(
     *         name="email",
     *         required=true,
     *         in="query",
     *         @OA\Schema (
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         required=true,
     *         in="query",
     *         @OA\Schema (
     *             type="string",
     *             format="password"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="password_confirmation",
     *         required=true,
     *         in="query",
     *         @OA\Schema (
     *             type="string",
     *             format="password"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="first_name",
     *         required=true,
     *         in="query",
     *         @OA\Schema (
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="last_name",
     *          required=true,
     *         in="query",
     *         @OA\Schema (
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="birthday",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string",
     *              format="date"
     *          )
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         required=true,
     *         in="query",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *          name="accept_terms",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="integer",
     *              enum={0,1}
     *          )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserProfile")
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
            $rules = [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed|min:6',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'username' => 'required|string',
                'birthday' => 'required|date_format:Y-m-d',
                'phone' => 'required|integer',
                'accept_terms' => 'required|boolean',
            ];

            $validated = $this->validate($request, $rules);

            $input = array_merge($validated, [
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'status' => User::STATUS_ACTIVE,
                'verify_token' => Str::random(32),
            ]);
            $user = User::create($input);

//            // Create new
//            $user = new User();
//            $user->fill($request->all());
//            $user->save();

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
                'title' => 'New user registration',
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
     *     @OA\Response(
     *         response="200",
     *         description="Data of user"
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
     *
     * @param mixed $id
     * @param Request $request
     *
     * @return mixed
     */
    public function show(Request $request, mixed $id): mixed
    {
        $builder = User::where('id', $id);
        //$builder = User::where('id', Auth::user()->id);

        $user = new User();
        if ($includes = $request->get('include')) {
            foreach (explode(',', $includes) as $include) {
                if (method_exists($user, $include) && $user->{$include}() instanceof Relation) {
                    $builder->with($include);
                }
            }
        }

        $user = $builder->firstOrFail();

        //$user = User::where('id', $id)->first();
        // TODO maybe we need to return public user data for everyone and secure user data for user
        //if (Auth::id() == $user->id) {
        //    return $user;
        //}

        // Get object
//        $user = $this->getObject($id);
//
//        if ($user instanceof JsonApiResponse) {
//            return $user;
//        }

        return response()->jsonApi([
            'type' => 'success',
            'title' => 'User details',
            'message' => "user details received",
            'data' => $user->toArray()
        ], 200);
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
     *         example="0aa06e6b-35de-3235-b925-b0c43f8f7c75",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserProfile")
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
     * @param mixed $id
     *
     * @return Response
     * @throws ValidationException
     */
    public function update(Request $request, mixed $id): Response
    {
        // Validate input
        $this->validate($request, User::validationRules());

        // Read user model
        $user = $this->getObject($id);
        if ($user instanceof JsonApiResponse) {
            return $user;
        }

        // Try update user data
        try {

                $validated = $this->validate($request, [
                    'phone' => "sometimes|integer",
                    'email' => "required|email|unique:users,email",
                    'current_password' => 'required_with:password|min:6',
                    'password' => 'required_with:current_password|confirmed|min:6|max:190',
                ]);

                $user = User::findOrFail($id);

//                // Update data
//                $user->fill($request->all());
//                $user->save();

                if (empty($user)) {
                    throw new Exception("User does not exist!");
                }

                if (!empty($validated['email']) && ($user->email == $validated['email'])) {
                    $user->status = User::STATUS_INACTIVE;
                    $user->verify_token = Str::random(32);
                    $user->save();

                    PubSub::transaction(function () use ($user) {
                        $user->save();
                    })->publish('sendVerificationEmail', [
                        'email' => $user->email,
                        'display_name' => $user->display_name,
                        'verify_token' => $user->verify_token,
                    ], 'mail');

                } else {
                    throw new BadRequestHttpException('Invalid credentials');
                }


                if ($request->has('current_password')) {
                    if (Hash::check($validated['current_password'], $user->password)) {
                        $validated['password'] = Hash::make($validated['password']);
                    } else {
                        throw new BadRequestHttpException('Invalid credentials');
                    }
                }

                if (!empty($validated)) {
                    $user->update($validated);
                    return response()->jsonApi(["message" => "Updated successfully"], 200);
                }
                throw new BadRequestHttpException();

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
     *         response="404",
     *         description="User not found"
     *     )
     *
     *     @OA\Response(
     *         response="500",
     *         description="Unknown error"
     *     )
     * )
     *
     * Remove the specified resource from storage.
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
     * Verify User
     *
     * @OA\Post(
     *     path="/admin/verify",
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
     *     path="/admin/verify/send",
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
}
