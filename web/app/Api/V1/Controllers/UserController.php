<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Resources\UserResource;
use App\Models\Category;
use App\Models\User;
use App\Services\IdentityVerification;
use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PubSub;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Traits\TokenHandler;

class UserController extends Controller
{
    use TokenHandler;

     /**
     * Return user data
     *
     * @OA\Get(
     *     path="/users",
     *     summary="Get current user profile",
     *     description="Get current user profile",
     *     tags={"User Profile"},
     *
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="success"
     *             ),
     *
     *             @OA\Property(
     *                 property="title",
     *                 type="string",
     *                 example="Valid Token"
     *             ),
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User Profile found"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="User object",
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     *
     */

    public function index()
    {
        $user = Auth::user();
        if ($user) {
            return response([
                'type' => 'success',
                'title' => "Valid Token",
                'message' => 'User Profile found',
                'data' => Auth::user()
            ]);
        }
        else {
            return response([
                'type' => 'danger',
                'title' => "Invalid Token",
                'message' => 'User Profile not found',
            ], 401);
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
     * @param int     $id
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

        $validatedData = $this->validate($request, User::personalValidationRules((int) $id));

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
     * Validate the new phone number that a user whats to use
     *
     * @OA\Post(
     *     path="/user-profile/validate-edit-phone",
     *     summary="Validate the new user phone number",
     *     description="Validate the new phone number that the current user whats to use",
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
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="phone number of the user",
     *              ),
     *          ),
     *     ),
     *
     *    @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="The given data was invalid."),
     *            @OA\Property(
     *               property="errors",
     *               type="object",
     *               @OA\Property(
     *                  property="phone",
     *                  type="array",
     *                  collectionFormat="multi",
     *                  @OA\Items(
     *                     type="string",
     *                     example={"The phone number is already taken.","The phone number is invalid."},
     *                  ),
     *               ),
     *            ),
     *         ),
     *      ),
     *
     *     @OA\Response(
     *        response=200,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="A 6-digit code has been sent to your phone number"),
     *        )
     *     ),
     *
     *    @OA\Response(
     *        response=500,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again."),
     *        )
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @throws \Exception
     * @return \Illuminate\Http\Response
     */
    public function validateEditPhoneNumber(Request $request)
    {
        $this->validate($request, [
            'phone' => [
                'required',
                'regex:/\+?\d{7,16}/i',
                "unique:users,phone",
            ],
        ]);

        try {
            $verificationCode = Str::random(6);
            $user = User::first(Auth::user()->id);
            $user->verification_code = Hash::make($verificationCode);

            if (!$user->save()) {
                throw new \Exception();
            }

            // Should send SMS to the user's new phone number, contaiing the verification code
            $response = Http::post('[COMMUNICATIONS_MS_URL]/messages/sms/send-message', [
                'to' => $request->phone,
                'message' => 'Your verification code is: ' . $verificationCode,
            ]);

            if (!$response->ok()) {
                throw new \Exception();
            }

            return response()->jsonApi(["message" => "A 6-digit code has been sent to your phone number"], 200);
        } catch (\Exception $e) {
            return response()->jsonApi(["message" => "An error occurred! Please, try again."], 500);
        }
    }

    /**
     * Validate the verification code and update phone number
     *
     * @OA\Post(
     *     path="/user-profile/update-phone",
     *     summary="Update current user's phone number",
     *     description="Validate the verification code and update phone number of the current user",
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
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  description="phone number of the user",
     *              ),
     *              @OA\Property(
     *                  property="verification_code",
     *                  type="string",
     *                  description="verification code previously send",
     *              ),
     *
     *          ),
     *     ),
     *
     *    @OA\Response(
     *        response=200,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Phone number updated"),
     *        )
     *     ),
     *
     *    @OA\Response(
     *        response=500,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again."),
     *        )
     *     ),
     *
     *    @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="The given data was invalid."),
     *            @OA\Property(
     *               property="errors",
     *               type="object",
     *               @OA\Property(
     *                  property="phone",
     *                  type="array",
     *                  collectionFormat="multi",
     *                  @OA\Items(
     *                     type="string",
     *                     example={"The phone number is already taken.","The phone number is invalid."},
     *                  )
     *               ),
     *
     *               @OA\Property(
     *                  property="verification_code",
     *                  type="array",
     *                  collectionFormat="multi",
     *                  @OA\Items(
     *                     type="string",
     *                     example={"The verification code is invalid."},
     *                  )
     *               ),
     *            )
     *         )
     *      )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @throws \Exception
     * @return \Illuminate\Http\Response
     */
    public function updateMyPhoneNumber(Request $request)
    {
        $rules = [
            'phone' => [
                'required',
                'regex:/\+?\d{7,16}/i',
                "unique:users,phone",
            ],
            'verification_code' => [
                'required',
                'regex:/\d{6}/i',
                function ($attribute, $value, $fail) {
                    $user = User::first(Auth::user()->id);
                    if (!Hash::check($value, $user->verification_code)) {
                        $fail('The verification code is invalid.');
                    }
                },
            ],
        ];

        $validationMessages = [
            'verification_code.regex' => 'The verification code is invalid',
        ];
        $this->validate($request, $rules, $validationMessages);

        try {
            $user = User::first(Auth::user()->id);
            $user->phone = $request->phone;
            $user->verification_code = null;
            if (!$user->save()) {
                throw new \Exception();
            }
            return response()->jsonApi(["message" => "Phone number updated"], 200);
        } catch (\Exception $e) {
            return response()->jsonApi(["message" => "An error occurred! Please, try again."], 500);
        }
    }

    /**
     * Validate the new email that the current user whats to use
     *
     * @OA\Post(
     *     path="/user-profile/validate-edit-email",
     *     summary="Validate the new user email",
     *     description="Validate the new email that the current user whats to use, and send verification code",
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
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="email of the user",
     *              ),
     *
     *          ),
     *     ),
     *
     *    @OA\Response(
     *        response=200,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="A 6-digit code has been sent to your email"),
     *        )
     *     ),
     *
     *    @OA\Response(
     *        response=500,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again."),
     *        )
     *     ),
     *
     *    @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="The given data was invalid."),
     *            @OA\Property(
     *               property="errors",
     *               type="object",
     *               @OA\Property(
     *                  property="email",
     *                  type="array",
     *                  collectionFormat="multi",
     *                  @OA\Items(
     *                     type="string",
     *                     example={"The email is already taken.","The email is invalid."},
     *                  )
     *               )
     *            )
     *         )
     *      )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @throws \Exception
     * @return \Illuminate\Http\Response
     */
    public function validateEditEmail(Request $request)
    {
        $this->validate($request, [
            'email' => [
                'required',
                'email',
                "unique:users,email",
            ],
        ]);

        try {
            $verificationCode = Str::random(6);
            $user = User::first(Auth::user()->id);
            $user->verification_code = Hash::make($verificationCode);

            if (!$user->save()) {
                throw new \Exception();
            }

            // Should send SMS to the user's new email contaiing the verification code
            $response = Http::post('[COMMUNICATIONS_MS_URL]/messages/email/send-message', [
                'to' => $request->email,
                'message' => 'Your verification code is: ' . $verificationCode,
            ]);

            if (!$response->ok()) {
                throw new \Exception();
            }

            return response()->jsonApi(["message" => "A 6-digit code has been sent to your email"], 200);
        } catch (\Exception $e) {
            return response()->jsonApi(["message" => "An error occurred! Please, try again."], 500);
        }
    }

    /**
     * Validate the verification code and update the current user's email
     *
     * @OA\Post(
     *     path="/user-profile/update-email",
     *     summary="Update current user's email",
     *     description="Validate the verification code and update the current user's email",
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
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  description="Email of the user",
     *              ),
     *              @OA\Property(
     *                  property="verification_code",
     *                  type="string",
     *                  description="verification code previously send",
     *              ),
     *
     *          ),
     *     ),
     *
     *    @OA\Response(
     *        response=200,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Email updated"),
     *        )
     *     ),
     *
     *    @OA\Response(
     *        response=500,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again."),
     *        )
     *     ),
     *
     *    @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="The given data was invalid."),
     *            @OA\Property(
     *               property="errors",
     *               type="object",
     *               @OA\Property(
     *                  property="phone",
     *                  type="array",
     *                  collectionFormat="multi",
     *                  @OA\Items(
     *                     type="string",
     *                     example={"The email is already taken.","The email is invalid."},
     *                  )
     *               ),
     *
     *               @OA\Property(
     *                  property="verification_code",
     *                  type="array",
     *                  collectionFormat="multi",
     *                  @OA\Items(
     *                     type="string",
     *                     example={"The verification code is invalid."},
     *                  )
     *               ),
     *            )
     *         )
     *      )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @throws \Exception
     * @return \Illuminate\Http\Response
     */
    public function updateMyEmail(Request $request)
    {
        $rules = [
            'phone' => [
                'required',
                'email',
                "unique:users,email",
            ],
            'verification_code' => [
                'required',
                'regex:/\d{6}/i',
                function ($attribute, $value, $fail) {
                    $user = User::first(Auth::user()->id);
                    if (!Hash::check($value, $user->verification_code)) {
                        $fail('The verification code is invalid.');
                    }
                },
            ],
        ];

        $validationMessages = [
            'verification_code.regex' => 'The verification code is invalid',
        ];
        $this->validate($request, $rules, $validationMessages);

        try {
            $user = User::first(Auth::user()->id);
            $user->email = $request->email;
            $user->verification_code = null;
            if (!$user->save()) {
                throw new \Exception();
            }
            return response()->jsonApi(["message" => "Email updated"], 200);
        } catch (\Exception $e) {
            return response()->jsonApi(["message" => "An error occurred! Please, try again."], 500);
        }
    }

    /**
     * Initialize identity verification session
     *
     * @OA\Post(
     *     path="/user-profile/identify",
     *     summary="Initialize identity verification session",
     *     description="Initialize identity verification session",
     *     description="Document type (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
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
     *             @OA\Property(
     *                 property="document_type",
     *                 type="string",
     *                 description="Document type (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
     *                 enum={"1", "2", "3", "4"},
     *                 example="1"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Identity verification session successfully initialized"
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
     * @param Request $request
     * @return mixed
     */
    public function identifyStart(Request $request): mixed
    {
        // Get user
        $user = User::find(Auth::user()->id);

        if (!$user) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Get user",
                'message' => "User with id #{$id} not found!",
                'data' => ''
            ], 404);
        }

        // Init verify session
        $data = (new IdentityVerification())->startSession($user, $request);

        // Return response to client
        if($data->status === 'success'){
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'Start KYC verification',
                'message' => "Session started successfully",
                'data' => $data->verification
            ], 200);
        }else{
            return response()->jsonApi([
                'type' => 'danger',
                'title' => 'Start KYC verification',
                'message' => $data->message,
                'data' => [
                    'code' => $data->code ?? ''
                ]
            ], 400);
        }
    }

    /**
     * Webhook to handle Veriff response
     *
     * @OA\Post(
     *     path="/user-profile/identify-webhook",
     *     summary="Webhook to handle Veriff response",
     *     description="Webhook to handle Veriff response",
     *     tags={"User Profile"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserIdentify")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="User identity verified successfully"
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
     * @return mixed
     */
    public function identifyWebHook(Request $request): mixed
    {
        // Validate input
        try {
            $this->validate($request, User::identifyValidationRules());
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'User data identification',
                'message' => "Validation error",
                'data' => $e->getMessage()
            ], 400);
        }

        // Try to save received document data
        try {
            // Find existing user
            $user = User::find(Auth::user()->id);
            if (!$user) {
                return response()->jsonApi([
                    'type' => 'danger',
                    'title' => "Get user",
                    'message' => "User with id #" . Auth::user()->id . " not found!",
                    'data' => ''
                ], 404);
            }


            // Transform data and save
            $identifyData = $request->all();
            foreach ($identifyData['document'] as $key => $value) {
                $identifyData['document_' . $key] = $value;
            }
            unset($identifyData['document']);

            $user->fill($identifyData);
            $user->status = User::STATUS_ACTIVE;
            $user->save();

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'New user registration',
                'message' => "User identity verified successfully",
                'data' => []
            ], 200);
        } catch (Exception $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'User data identification',
                'message' => "Unknown error",
                'data' => $e->getMessage()
            ], 500);
        }
    }
}
