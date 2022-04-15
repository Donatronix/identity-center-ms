<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Resources\UserResource;
use App\Models\Category;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PubSub;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserController extends Controller
{
    /**
     * Create new user for One-Step
     *
     * @OA\Post(
     *     path="/user-profile",
     *     summary="Create new user for One-Step",
     *     description="Create new user for One-Step",
     *     tags={"User Profile"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"phone"},
     *
     *             @OA\Property(
     *                 property="phone",
     *                 type="number",
     *                 description="Phone number of user",
     *                 example="380971829100"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response=201,
     *          description="Success",
     *
     *          @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 example="Create new user. Step 1"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User was successful created"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                     example="50000005-5005-5005-5005-500000000005"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="number",
     *                     example="380971829100"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *
     *          @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="danger"
     *             ),
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 example="Create new user. Step 1"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example=""
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *                 example=""
     *             )
     *         )
     *     )
     * )
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate input data
        $this->validate($request, [
            'phone' => 'required|integer',
        ]);

        // Try to create new user
        try {
            $user = User::create($request->all());

            // Return response
            return response()->json([
                'type' => 'success',
                'title' => "Create new user. Step 1",
                'message' => 'User was successful created',
                'data' => $user
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => "Create new user. Step 1",
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Return user data
     *
     * @OA\Get(
     *     path="/user-profile/me",
     *     summary="Get current user profile",
     *     description="Get current user profile",
     *     tags={"User Profile"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
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
     * @param         $id
     * @param Request $request
     *
     * @return mixed
     */
    public function show(Request $request)
    {
        $builder = User::where('id', Auth::user()->id);

        $user = new User();
        if ($includes = $request->get('include')) {
            foreach (explode(',', $includes) as $include) {
                if (method_exists($user, $include) && $user->{$include}() instanceof Relation) {
                    $builder->with($include);
                }
            }
        }

        try {
            $user = $builder->firstOrFail();
        } catch (Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => "Not Found",
                'message' => " User not found"
            ], 404);
        }

        //$user = User::where('id', $id)->first();
        // TODO maybe we need to return public user data for everyone and secure user data for user
        //if (Auth::id() == $user->id) {
        //    return $user;
        //}

        return response()->jsonApi([
            'type' => 'success',
            'data' => $user
        ]);
    }

    /**
     * Update the specified resource in storage
     *
     * @OA\Patch(
     *     path="/user-profile/{id}",
     *     summary="update user",
     *     description="update user",
     *     tags={"User Profile"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
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
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'phone' => "integer",
            'email' => "email|unique:users,email",
            'current_password' => 'required_with:password|min:6',
            'password' => 'required_with:current_password|confirmed|min:6|max:190',
        ]);

        $user = User::findOrFail($id);

        if (!empty($request->email)) {
            $user->status = User::STATUS_ACTIVE;
            $user->verify_token = Str::random(32);

            PubSub::transaction(function () use ($user) {
                $user->save();
            })->publish('sendVerificationEmail', [
                'email' => $user->email,
                'display_name' => $user->display_name,
                'verify_token' => $user->verify_token,
            ], 'mail');
        }

        $update = $request->except(['password']);

        if ($request->has('current_password')) {
            if (Hash::check($request->current_password, $user->password)) {
                $update['password'] = Hash::make($request->password);
            } else {
                throw new BadRequestHttpException('Invalid current_password');
            }
        }

        if (!empty($update)) {
            $user->fill($update);
            $user->save();

            return response()->jsonApi(["message" => "updated"], 200);
        }

        throw new BadRequestHttpException();
    }

    /**
     * Verify user email
     *
     * @OA\Post(
     *     path="/user-profile/verify/send",
     *     summary="Verify user email",
     *     description="resend user email",
     *     tags={"User Profile"},
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
     *         response=200,
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify_email(Request $request)
    {
        $this->validate($request, [
            'email' => "required|email"
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        PubSub::publish('sendVerificationEmail', [
            'email' => $user->email,
            'display_name' => $user->display_name,
            'verify_token' => $user->verify_token,
        ], 'mail');

        return response()->jsonApi(["email sent"], 200);
    }

    /**
     * @OA\Post(
     *     path="/user-profile/verify",
     *     summary="Verify user email",
     *     description="Verify user email",
     *     tags={"User Profile"},
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
     *          name="verify_token",
     *          required=true,
     *          in="query",
     *          @OA\Schema (
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *     )
     * )
     */
}
