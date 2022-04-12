<?php

namespace App\Api\V1\Controllers\Admin;

use App\Api\V1\Controllers\Controller;
use App\Api\V1\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PubSub;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $rules = [
            'id' => 'required|distinct|min:1'
        ];

        $this->validate($request, $rules);

        $id = explode(',', $request->id);

        $users = User::select('id', 'display_name')->whereIn('id', $id)->get();

        return response()->jsonApi($users, 200);
    }

    /**
     * Register User
     *
     * @OA\Post(
     *     path="/admin",
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
     * @return User|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // TODO fix date format (for birthday)
        $rules = [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'birthday' => 'required|date_format:Y-m-d',
            'phone' => 'required|integer',
            'accept_terms' => 'required|boolean'
        ];

        $this->validate($request, $rules);

        $input = $request->all();
        $user = User::create($input);
        $user->password = Hash::make($input['password']);
        //$user->status = User::STATUS_ACTIVE;
        $user->verify_token = Str::random(32);

        PubSub::transaction(function () use ($user) {
            $user->save();
        })->publish('sendVerificationEmail', [
            'email' => $user->email,
            'display_name' => $user->display_name,
            'verify_token' => $user->verify_token,
        ], 'mail');

        return response()->jsonApi(null, 201);
    }

    /**
     * Return user data
     *
     * @OA\Get(
     *     path="/admin/{id}",
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
     * @param         $id
     * @param Request $request
     *
     * @return mixed
     */
    public function show(Request $request, $id)
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
        if ($user) {
            UserResource::withoutWrapping();

            return new UserResource($user);
        }
    }

    /**
     * Update the specified resource in storage
     *
     * @OA\Patch(
     *     path="/admin/{id}",
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
            $user->status = User::STATUS_INACTIVE;
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
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Verify user email
     *
     * @OA\Post(
     *     path="/admin/one-step/verify/send",
     *     summary="Verify user email",
     *     description="resend user email",
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
     *     path="/admin/one-step/verify",
     *     summary="Verify user email",
     *     description="Verify user email",
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
