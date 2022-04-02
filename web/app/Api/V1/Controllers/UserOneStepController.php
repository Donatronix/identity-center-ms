<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Resources\UserResource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PubSub;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserOneStepController extends Controller
{
    /**
     * Create new user for One-Step
     *
     * @OA\Post(
     *     path="/users/one-step",
     *     summary="Create new user for One-Step",
     *     description="Create new user for One-Step",
     *     tags={"One-Step Users"},
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
    public function store(Request $request): \Illuminate\Http\JsonResponse
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
        } catch (\Exception $e) {
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
     *     path="/users/one-step/me",
     *     summary="Get current user profile",
     *     description="Get current user profile",
     *     tags={"One-Step Users"},
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

        $user = $builder->firstOrFail();

        //$user = User::where('id', $id)->first();
        // TODO maybe we need to return public user data for everyone and secure user data for user
        //if (Auth::id() == $user->id) {
        //    return $user;
        //}
        if ($user) {
            UserResource::withoutWrapping();

            return new UserResource($user);
        }
    }

    /**
     * Update the specified resource in storage
     *
     * @OA\Patch(
     *     path="/users/one-step/{id}",
     *     summary="update user",
     *     description="update user",
     *     tags={"One-Step Users"},
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
}
