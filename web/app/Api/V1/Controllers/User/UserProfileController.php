<?php

namespace App\Api\V1\Controllers\User;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
use App\Services\SendEmailNotify;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class UserProfileController
 *
 * @package App\Api\V1\Controllers\User
 */
class UserProfileController extends Controller
{
    /**
     * Get current user profile data
     *
     * @OA\Get(
     *     path="/user-profile/me",
     *     summary="Get current user profile data",
     *     description="Get current user profile data",
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
     *                 example="Get current user profile data"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User profile data retrieved successfully."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                     example="John"
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                     example="Kiels"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     example="Kiels@onestep.com"
     *                 ),
     *                 @OA\Property(
     *                     property="country",
     *                     type="string",
     *                     example="United Kindom"
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
     *                 example="Get current user profile data"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User data not found."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        Auth::user()->id = '10000000-1000-1000-1000-000000000001';

//        $user = Auth::user();
//        if ($user) {}

        try {
            $builder = User::where('id', Auth::user()->id);

            // Check whether user already exist
            if ($builder->exists()) {
                // Add relations to object
                $user = new User();
                if ($includes = $request->get('include')) {
                    foreach (explode(',', $includes) as $include) {
                        if (method_exists($user, $include) && $user->{$include}() instanceof Relation) {
                            $builder->with($include);
                        }
                    }
                }

                // Fetch user profile
                $user = $builder->select(
                    'first_name',
                    'last_name',
                    'email',
                    'address_country',
                    'locale'
                )->firstOrFail();

                // Return response
                return response()->jsonApi([
                    'type' => 'success',
                    'title' => 'Get current user profile data',
                    'message' => '"User profile retrieved successfully',
                    'data' => $user->toArray(),
                ]);
            } else {
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'type' => 'danger',
                'title' => 'Get current user profile data',
                'message' => "Unable to retrieve user profile.",
                "data" => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => 'Get current user profile data',
                'message' => $e->getMessage(),
                'data' => []
            ], 404);
        }
    }

    /**
     * Create new user for One-Step
     *
     * @OA\Post(
     *     path="/users",
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate input data
        $this->validate($request, [
            'phone' => 'required|integer',
        ]);

        // Try to create new user
        try {
            $user = null;
            PubSub::transaction(function () use ($request, &$user) {
                $user = User::create(array_merge($request->all(), ['phone' => $request->get('phone')]));
            })->publish('NewUserRegistered', [
                'user' => $user?->toArray(),
            ], 'new_user');

            // Return response
            return response()->json([
                'type' => 'success',
                'title' => "Create new user. Step 1",
                'message' => 'User was successful created',
                'data' => $user,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => "Create new user. Step 1",
                'message' => $e->getMessage(),
            ], 400);
        }
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
     *     @OA\Parameter(
     *          description="ID of User",
     *          in="path",
     *          name="id",
     *          required=true,
     *          example="1",
     *          @OA\Schema(
     *              type="integer",
     *              format="int64"
     *          ),
     *     ),
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead"
     *         }
     *     }},
     *
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="first_name",
     *                  type="string",
     *                  description="First name",
     *              ),
     *              @OA\Property(
     *                  property="last_name",
     *                  type="string",
     *                  description="Last name",
     *              ),
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email address",
     *              ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="Phone number",
     *              ),
     *              @OA\Property(
     *                  property="birthday",
     *                  type="string",
     *                  description="Date of birth in format DD-MM-YYYY",
     *              ),
     *              @OA\Property(
     *                  property="subscribed_to_announcement",
     *                  type="string",
     *                  description="Indicate whether or not the user should be subscribed for announcements",
     *              ),
     *              @OA\Property(
     *                  property="address_country",
     *                  type="string",
     *                  description="Country code",
     *              ),
     *              @OA\Property(
     *                  property="address_line1",
     *                  type="string",
     *                  description="First line of address. may contain house number, street name, etc.",
     *              ),
     *              @OA\Property(
     *                  property="address_line2",
     *                  type="string",
     *                  description="Second line of address.",
     *              ),
     *              @OA\Property(
     *                  property="address_city",
     *                  type="string",
     *                  description="Name of city",
     *              ),
     *              @OA\Property(
     *                  property="address_zip",
     *                  type="string",
     *                  description="Zip code",
     *              ),
     *
     *          ),
     *     ),
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
     * @param int $id
     *
     * @return Response
     * @throws ValidationException
     */
    public function update(Request $request, int $id): Response
    {
//        $this->validate($request, [
//            'phone' => "integer",
//            'email' => "email|unique:users,email",
//            'current_password' => 'required_with:password|min:6',
//            'password' => 'required_with:current_password|confirmed|min:6|max:190',
//        ]);

        $validatedData = $this->validate($request, User::personalValidationRules((int)$id));

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

//        $update = $request->except(['password']);
//
//        if ($request->has('current_password')) {
//            if (Hash::check($request->current_password, $user->password)) {
//                $update['password'] = Hash::make($request->password);
//            } else {
//                throw new BadRequestHttpException('Invalid current_password');
//            }
//        }
//
//        if (!empty($update)) {
//            $user->fill($update);
//            $user->save();

        if (!empty($validatedData)) {
            $user->fill($validatedData);
            $user->save();

            return response()->jsonApi(["message" => "updated"], 200);
        }

        throw new BadRequestHttpException();
    }

    /**
     * Change user profile password for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/password/change",
     *     summary="Change user password",
     *     description="Change user profile password for One-Step 2.0",
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
     *
     *             @OA\Property(
     *                 property="id",
     *                 type="string",
     *                 description="User ID for user profile update",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *             @OA\Property(
     *                 property="current_password",
     *                 type="string",
     *                 description="Current user password for profile update",
     *                 required={"true"},
     *                 example="XXXXXXXX"
     *             ),
     *             @OA\Property(
     *                 property="new_password",
     *                 type="string",
     *                 description="New user password for profile update",
     *                 required={"true"},
     *                 example="XXXXXXXX"
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
     *                 property="message",
     *                 type="string",
     *                 example="User profile password changed successfully."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object"
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
     *                 property="message",
     *                 type="string",
     *                 example="Unable to change profile password."
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
     * @param Request $request
     * @param SendEmailNotify $sendEmail
     *
     * @return JsonResponse
     */
    public function updatePassword(Request $request, SendEmailNotify $sendEmail): JsonResponse
    {
        $validData = $this->validate($request, [
            'id' => 'required|string',
            'current_password' => 'required|string|max:32',
            'new_password' => 'required|string|max:32'
        ]);

        try {
            // Verify current password
            $userQuery = User::where('id', $validData['id']);

            $user = $userQuery->firstOrFail();

            if (Hash::check($validData['current_password'], $user->password)) {

                $newPass = Hash::make($validData['new_password']);

                // Update user password
                $userQuery->update([
                    'password' => $newPass
                ]);

                //Send notification email
                $subject = 'Change Password';
                $message = 'Your password has been updated successfully.';
                $sendEmail->dispatchEmail($to['email'], $subject, $message);

                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "User password updated successfully.",
                    "data" => null
                ], 200);

            } else {
                return response()->json([
                    'type' => 'danger',
                    'message' => "Invalid user password. Try again",
                    "data" => null
                ], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update user password.",
                "data" => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update username for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/username/update",
     *     summary="Update username",
     *     description="Update username for One-Step 2.0",
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
     *
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="User username for user profile update",
     *                 example="john.kiels"
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
     *                 property="message",
     *                 type="string",
     *                 example="Username update was successful"
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
     *                 property="message",
     *                 type="string",
     *                 example="Username update FAILED"
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @param SendEmailNotify $sendEmail
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateUsername(Request $request, SendEmailNotify $sendEmail): JsonResponse
    {
        //validate input date
        $input = $this->validate($request, [
            'username' => 'required|string'
        ]);

        try {
            // Check whether user already exist
            $userQuery = User::where(['username' => $input['username']]);

            if ($userQuery->exists()) {

                $user = $userQuery->firstOrFail();

                //Update username
                $user->update([
                    'username' => $input['username']
                ]);

                //Send notification email
                $subject = 'Change Username';
                $message = 'Your username has been updated successfully.';
                $sendEmail->dispatchEmail($to['email'], $subject, $message);

                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Username update was successful."
                ], 400);
            } else {
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update Username.",
                "data" => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update username for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/fullname/update",
     *     summary="Update fullname",
     *     description="Update fullname for One-Step 2.0",
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
     *
     *             @OA\Property(
     *                 property="id",
     *                 type="string",
     *                 description="User ID for user profile update",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *             @OA\Property(
     *                 property="firstname",
     *                 type="string",
     *                 description="User firstname for user profile update",
     *                 example="john"
     *             ),
     *             @OA\Property(
     *                 property="lastname",
     *                 type="string",
     *                 description="User lastname for user profile update",
     *                 example="kiels"
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
     *                 property="message",
     *                 type="string",
     *                 example="Full name update was successful"
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
     *                 property="message",
     *                 type="string",
     *                 example="Full name update FAILED"
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateFullname(Request $request): JsonResponse
    {
        //validate input date
        $input = $this->validate($request, [
            'id' => 'required|string',
            'firstname' => 'required|string',
            'lastname' => 'required|string'
        ]);

        try {
            // Check whether user already exist
            $userQuery = User::where(['id' => $input['id']]);

            if ($userQuery->exists()) {

                $user = $userQuery->firstOrFail();

                //Update full name
                $user->update([
                    'first_name' => $input['firstname'],
                    'last_name' => $input['lastname']
                ]);

                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Full name update was successful."
                ], 400);
            } else {
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update Full name.",
                "data" => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update Country for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/country/update",
     *     summary="Update country",
     *     description="Update country for One-Step 2.0",
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
     *
     *            @OA\Property(
     *                 property="id",
     *                 type="string",
     *                 description="User ID for user profile update",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *             @OA\Property(
     *                 property="country",
     *                 type="string",
     *                 description="User country for user profile update",
     *                 example="United Kindom"
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
     *                 property="message",
     *                 type="string",
     *                 example="Country update was successful"
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
     *                 property="message",
     *                 type="string",
     *                 example="Country update FAILED"
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateCountry(Request $request): JsonResponse
    {
        //validate input date
        $input = $this->validate($request, [
            'id' => 'required|string',
            'country' => 'required|string'
        ]);

        try {
            // Check whether user already exist
            $userQuery = User::where(['id' => $input['id']]);

            if ($userQuery->exists()) {

                $user = $userQuery->firstOrFail();

                //Update username
                $user->update([
                    'country' => $input['country']
                ]);

                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Country update was successful."
                ], 400);
            } else {
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update country.",
                "data" => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update email for One-Step 2.0
     *
     * @OA\Put(
     *     path="/user-profile/email/update",
     *     summary="Update email",
     *     description="Update email for One-Step 2.0",
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
     *
     *            @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 description="User email for user profile update",
     *                 example="johnkiels@ultainfinity.com"
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
     *                 property="message",
     *                 type="string",
     *                 example="Email update was successful"
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
     *                 property="message",
     *                 type="string",
     *                 example="Email update FAILED"
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateEmail(Request $request): JsonResponse
    {
        //validate input date
        $input = $this->validate($request, [
            'email' => 'required|string|email'
        ]);

        try {
            // Check whether user already exist
            $userQuery = User::where(['email' => $input['email']]);

            if ($userQuery->exists()) {

                $user = $userQuery->firstOrFail();

                //Update username
                $user->update([
                    'email' => $input['email']
                ]);

                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Email update was successful."
                ], 400);
            } else {
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update email.",
                "data" => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update user profile locale
     *
     * @OA\Put(
     *     path="/user-profile/locale/update",
     *     summary="Update user profile locale",
     *     description="Update user profile locale",
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
     *
     *            @OA\Property(
     *                 property="id",
     *                 type="string",
     *                 description="User ID for user profile update",
     *                 required={"true"},
     *                 example="373458be-3f01-40ca-b6f3-245239c7889f"
     *             ),
     *             @OA\Property(
     *                 property="locale",
     *                 type="string",
     *                 description="Update user profile locale",
     *                 example="UK English"
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
     *                 property="message",
     *                 type="string",
     *                 example="Local update was successful"
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
     *                 property="message",
     *                 type="string",
     *                 example="Local update FAILED"
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateLocal(Request $request): JsonResponse
    {
        //validate input date
        $input = $this->validate($request, [
            'id' => 'required|string',
            'locale' => 'required|string'
        ]);

        try {
            // Check whether user already exist
            $userQuery = User::where(['id' => $input['id']]);

            if ($userQuery->exists()) {

                $user = $userQuery->firstOrFail();

                //Update username
                $user->update([
                    'locale' => $input['locale']
                ]);

                //Show response
                return response()->json([
                    'type' => 'success',
                    'message' => "Email update was successful."
                ], 400);
            } else {
                return response()->json([
                    'type' => 'danger',
                    'message' => "User profile does NOT exist.",
                    "data" => null
                ], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'type' => 'danger',
                'message' => "Unable to update email.",
                "data" => $e->getMessage()
            ], 400);
        }
    }
}
