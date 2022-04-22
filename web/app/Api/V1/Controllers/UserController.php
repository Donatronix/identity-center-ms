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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
     *                  property="email",
     *                  type="string",
     *                  description="email of the user",
     *              ),
     *              @OA\Property(
     *                  property="phone_number",
     *                  type="string",
     *                  description="phone number of the user",
     *              ),
     *              @OA\Property(
     *                  property="birthday",
     *                  type="string",
     *                  description="Users date of birth in format DD-MM-YYYY",
     *              ),
     *              @OA\Property(
     *                  property="subscribed_to_announcement",
     *                  type="string",
     *                  description="Indicate whether or not the user should be subscribed for announcements",
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validatedData = $this->validate($request, [
            'phone_number' => "sometimes|regex:/\+?\d{7,16}/i|unique:users,phone_number",
            'email' => "sometimes|email|unique:users,email",
            'birthday' => 'sometimes|nullable|date_format:d-m-Y',
            'subscribed_to_announcement' => 'sometimes|boolean',
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
     * @OA\Patch(
     *     path="/user-profile/validate-edit-phone",
     *     summary="Validate the new user phone number",
     *     description="Validate the new phone number that a user whats to use",
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
     *                  property="phone_number",
     *                  type="string",
     *                  description="phone number of the user",
     *              ),
     *
     *          ),
     *     ),
     *
     *    @OA\Response(
     *        response=200,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="A 6-digit code has been sent to your phone number")"),
     *        )
     *     )
     *
     *    @OA\Response(
     *        response=500,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again.")"),
     *        )
     *     )
     *    @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="The given data was invalid."),
     *            @OA\Property(
     *               property="errors",
     *               type="object",
     *               @OA\Property(
     *                  property="phone_number",
     *                  type="array",
     *                  collectionFormat="multi",
     *                  @OA\Items(
     *                     type="string",
     *                     example={"The phone number is already taken.","The phone number is invalid."},
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
    public function validateEditPhoneNumber(Request $request)
    {
        $this->validate($request, [
            'phone_number' => [
                'required',
                'regex:/\+?\d{7,16}/i',
                "unique:users,phone_number",
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
                'to' => $request->phone_number,
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
     * @OA\Patch(
     *     path="/user-profile/validate-edit-phone",
     *     summary="Update user phone number",
     *     description="Validate the verification code and update phone number",
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
     *                  property="phone_number",
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
     *           @OA\Property(property="message", type="string", example="Phone number updated")"),
     *        )
     *     )
     *
     *    @OA\Response(
     *        response=500,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again.")"),
     *        )
     *     )
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
     *                  property="phone_number",
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
            'phone_number' => [
                'required',
                'regex:/\+?\d{7,16}/i',
                "unique:users,phone_number",
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
            $user->phone_number = $request->phone_number;
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
     * Validate the new email that a user whats to use
     *
     * @OA\Patch(
     *     path="/user-profile/validate-edit-email",
     *     summary="Validate the new user email",
     *     description="Validate the new email that a user whats to use, and send verification code",
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
     *           @OA\Property(property="message", type="string", example="A 6-digit code has been sent to your email")"),
     *        )
     *     )
     *
     *    @OA\Response(
     *        response=500,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again.")"),
     *        )
     *     )
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
     * @OA\Patch(
     *     path="/user-profile/validate-edit-email",
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
     *           @OA\Property(property="message", type="string", example="Email updated")"),
     *        )
     *     )
     *
     *    @OA\Response(
     *        response=500,
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again.")"),
     *        )
     *     )
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
     *                  property="phone_number",
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
            'phone_number' => [
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
