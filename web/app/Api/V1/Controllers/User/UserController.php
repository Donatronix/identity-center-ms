<?php

namespace App\Api\V1\Controllers\User;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
use App\Traits\TokenHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PubSub;

class UserController extends Controller
{
    use TokenHandler;

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
     *             "User",
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
     *         response="200",
     *         description="Success"
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Not found"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function verify_email(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => "required|email",
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
     *        response="200",
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="A 6-digit code has been sent to your phone number"),
     *        )
     *     ),
     *
     *    @OA\Response(
     *        response="500",
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="An error occurred! Please, try again."),
     *        )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
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
                throw new Exception();
            }

            // Should send SMS to the user's new phone number, contaiing the verification code
            $response = Http::post('[COMMUNICATIONS_MS_URL]/messages/sms/send-message', [
                'to' => $request->get('phone', null),
                'message' => 'Your verification code is: ' . $verificationCode,
            ]);

            if (!$response->ok()) {
                throw new Exception();
            }

            return response()->jsonApi(["message" => "A 6-digit code has been sent to your phone number"], 200);
        } catch (Exception $e) {
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
     *        response="200",
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Phone number updated"),
     *        )
     *     ),
     *
     *    @OA\Response(
     *        response="500",
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
     * @param Request $request
     *
     * @return Response
     * @throws Exception
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
            $user->phone = $request->get('phone', null);
            $user->verification_code = null;
            if (!$user->save()) {
                throw new Exception();
            }
            return response()->jsonApi(["message" => "Phone number updated"], 200);
        } catch (Exception $e) {
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
     *        response="200",
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="A 6-digit code has been sent to your email"),
     *        )
     *     ),
     *
     *    @OA\Response(
     *        response="500",
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
     * @param Request $request
     *
     * @return Response
     * @throws Exception
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
                throw new Exception();
            }

            // Should send SMS to the user's new email contaiing the verification code
            $response = Http::post('[COMMUNICATIONS_MS_URL]/messages/email/send-message', [
                'to' => $request->email,
                'message' => 'Your verification code is: ' . $verificationCode,
            ]);

            if (!$response->ok()) {
                throw new Exception();
            }

            return response()->jsonApi(["message" => "A 6-digit code has been sent to your email"], 200);
        } catch (Exception $e) {
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
     *        response="200",
     *        description="Validation success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Email updated"),
     *        )
     *     ),
     *
     *    @OA\Response(
     *        response="500",
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
     * @param Request $request
     *
     * @return Response
     * @throws Exception
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
                throw new Exception();
            }
            return response()->jsonApi(["message" => "Email updated"], 200);
        } catch (Exception $e) {
            return response()->jsonApi(["message" => "An error occurred! Please, try again."], 500);
        }
    }
}
