<?php

namespace App\Api\V1\Controllers\Admin;

use App\Api\V1\Controllers\Controller;
use App\Listeners\NewUserRegisteredListener;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class UserController extends Controller
{
    /**
     *  Display a listing of the users
     *
     * @OA\Get(
     *     path="/admin/users",
     *     description="Get all users",
     *     tags={"Users"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "User",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     x={
     *          "auth-type": "Applecation & Application Use",
     *          "throttling-tier": "Unlimited",
     *          "wso2-appliocation-security": {
     *              "security-types": {"oauth2"},
     *              "optional": "false"
     *           },
     *     },
     *
     *     @OA\Response(
     *         response="200",
     *         description="Output data",
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
     *                     property="phone_number",
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
     *                  property="id",
     *                  type="string",
     *                  description="Uuid user not found"
     *              ),
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  description="Username not found"
     *              ),
     *              @OA\Property(
     *                  property="platform",
     *                  type="string",
     *                  description="Platform not found"
     *              ),
     *              @OA\Property(
     *                  property="total_users",
     *                  type="string",
     *                  description="Total user not found"
     *              ),
     *              @OA\Property(
     *                  property="new_users_count_week",
     *                  type="string",
     *                  description="No new users this week"
     *              ),
     *              @OA\Property(
     *                  property="new_users_count_month",
     *                  type="string",
     *                  description="No new users this month"
     *              ),
     *              @OA\Property(
     *                  property="total_earning",
     *                  type="string",
     *                  description="No total earnings information found"
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
            $users = User::query()->paginate($request->get('limit', config('settings.pagination_limit')));

            return response()->jsonApi(
                array_merge([
                    'type' => 'success',
                    'title' => 'Operation was success',
                    'message' => 'The data was displayed successfully',
                ], $users->toArray()),
                200);

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Not operation",
                'message' => "Error showing all transactions",
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
    }

    /**
     * Register User
     *
     * @OA\Post(
     *     path="/admin/users",
     *     summary="Create new user",
     *     description="Create new user",
     *     tags={"Admin / Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *          name="email",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="password",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string",
     *              format="password"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="password_confirmation",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string",
     *              format="password"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="first_name",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="last_name",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="birthday",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string",
     *              format="date"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="phone",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string"
     *          )
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
     *     @OA\Response(
     *          response=200,
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Bad Request"
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
        try {
            DB::transaction(function () use ($request) { // TODO fix date format (for birthday)
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
                    'phone_number' => $validated['phone'],
                    'password' => Hash::make($validated['password']),
                    'status' => User::STATUS_ACTIVE,
                    'verify_token' => Str::random(32),
                ]);
                $user = User::query()->create($input);

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

            });
        } catch (Throwable $th) {
            return response()->jsonApi(['message' => $th->getMessage()], 400);
        }
        return response()->jsonApi(["message" => "User registered successfully!"], 200);
    }

    /**
     * Return user data
     *
     * @OA\Get(
     *     path="/admin/users/{id}",
     *     summary="Get user details",
     *     description="Get user details",
     *     tags={"Admin / Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found"
     *     )
     * )
     *
     * @param mixed   $id
     * @param Request $request
     *
     * @return mixed
     */
    public function show(Request $request, mixed $id): mixed
    {
        $builder = User::query()->where('id', $id);
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
        return $user;

    }

    /**
     * Update the specified resource in storage
     *
     * @OA\Patch(
     *     path="/admin/users/{id}",
     *     summary="update user",
     *     description="update user",
     *     tags={"Admin / Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found"
     *     )
     * )
     *
     * @param Request $request
     * @param mixed   $id
     *
     * @return Response
     * @throws ValidationException
     */
    public function update(Request $request, mixed $id): Response
    {
        try {
            DB::transaction(function () use ($request, $id) {

                $validated = $this->validate($request, [
                    'phone' => "sometimes|integer",
                    'email' => "required|email|unique:users,email",
                    'current_password' => 'required_with:password|min:6',
                    'password' => 'required_with:current_password|confirmed|min:6|max:190',
                ]);

                $user = User::query()->findOrFail($id);

                if (empty($user)) {
                    throw new Exception("User does not exist!");
                }

                if (!empty($validated['email']) && ($user->email == $validated['email'])) {
                    $user->status = User::STATUS_INACTIVE;
                    $user->verify_token = Str::random(32);
                    $user->save();
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

            });
        } catch (Throwable $th) {
            return response()->jsonApi(["message" => $th->getMessage()], 200);
        }
        return response()->jsonApi(["message" => "Updated successfully"], 200);
    }

    /**
     *  Delete user record
     *
     * @OA\Delete(
     *     path="/admin/users/{id}",
     *     description="Delete user",
     *     tags={"Users"},
     *
     *     security={{
     *          "default" :{
     *              "ManagerRead",
     *              "user",
     *              "ManagerWrite"
     *          },
     *     }},
     *
     *     x={
     *          "auth-type": "Applecation & Application Use",
     *          "throttling-tier": "Unlimited",
     *          "wso2-appliocation-security": {
     *              "security-types": {"oauth2"},
     *              "optional": "false"
     *           },
     *     },
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="user user id",
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
     *                 property="message",
     *                 type="string",
     *                 description="Success or error message",
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
     *                  property="id",
     *                  type="string",
     *                  description="Uuid user not found"
     *              ),
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  description="Username not found"
     *              ),
     *              @OA\Property(
     *                  property="platform",
     *                  type="string",
     *                  description="Platform not found"
     *              ),
     *              @OA\Property(
     *                  property="total_users",
     *                  type="string",
     *                  description="Total user not found"
     *              ),
     *              @OA\Property(
     *                  property="new_users_count_week",
     *                  type="string",
     *                  description="No new users this week"
     *              ),
     *              @OA\Property(
     *                  property="new_users_count_month",
     *                  type="string",
     *                  description="No new users this month"
     *              ),
     *              @OA\Property(
     *                  property="total_earning",
     *                  type="string",
     *                  description="No total earnings information found"
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
     *
     * Remove the specified resource from storage.
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function destroy(mixed $id): mixed
    {
        try {
            $users = null;
            DB::transaction(function () use ($id, &$users) {
                $user = User::query()->findOrFail($id);
                $user->delete();
                $users = User::query()->paginate(config('settings.pagination_limit'));
            });

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Delete failed",
                'message' => "User does not exist",
                'data' => null,
            ], 404);
        } catch (Throwable $th) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Delete failed",
                'message' => $th->getMessage(),
                'data' => null,
            ], 404);
        }
        return response()->jsonApi([
            'type' => 'success',
            'title' => 'Operation was a success',
            'message' => 'User was deleted successfully',
            'data' => $users->toArray(),
        ], 200);
    }

    /**
     * Verify User
     *
     * @OA\Post(
     *     path="/admin/verify",
     *     summary="Create new user",
     *     description="Create new user",
     *     tags={"Admin / Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *          name="token",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Bad Request"
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


                $user = User::query()->where('email', $select->first()->email)->first();
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
                'data' => null,
            ], 404);
        }

        return response()->jsonApi([
            'type' => 'success',
            'title' => "Verification successful",
            'message' => "Email is verified",
            'data' => null,
        ], 200);
    }

    /**
     * Send verification email
     *
     * @OA\Post(
     *     path="/admin/verify/send",
     *     summary="Create new user",
     *     description="Create new user",
     *     tags={"Admin / Users"},
     *
     *     security={{
     *         "passport": {
     *             "ManagerRead",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Parameter(
     *          name="email",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Bad Request"
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

                $user = User::query()->where('email', $validated['email'])->first();

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
                'data' => null,
            ], 404);
        }
        return response()->jsonApi([
            'type' => 'success',
            'title' => "Verification email",
            'message' => "A verification mail has been sent",
            'data' => null,
        ], 200);
    }
}
