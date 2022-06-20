<?php

namespace App\Api\V1\Controllers\User;

use App\Exceptions\UserRegistrationException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Sumra\SDK\JsonApiResponse;

/**
 * Class UserController
 *
 * @package App\Api\V1\Controllers
 */
class UserController extends Controller
{
    /**
     * User registration
     * Step 2. Saving user person detail
     *
     * @OA\Post(
     *     path="/users2",
     *     summary="Saving user person detail",
     *     description="Saving user person detail",
     *     tags={"Users"},
     *
     *     security={{
     *         "passport": {
     *             "User",
     *             "ManagerRead",
     *             "ManagerWrite"
     *         }
     *     }},
     *
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
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request): mixed
    {
        // Validate input
        try {
            $this->validate($request, User::personValidationRules());
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'User person details data',
                'message' => "Validation error",
                'data' => $e->getMessage()
            ], 400);
        }

        // Try to save received data
        try {
            // Get user_id as user_Id
            $user_id = Auth::user()->getAuthIdentifier();

            // Find exist user
            $user = User::find($user_id);

            // If not exist, then to create it
            if (!$user) {
                // Create new
                $user = User::create([
                    'id' => $user_id,
                    'status' => User::STATUS_STEP_1
                ]);
            }

            // Convert address field and save person data
            $personData = $request->all();
            foreach ($personData['address'] as $key => $value) {
                $personData['address_' . $key] = $value;
            }
            unset($personData['address']);

            $user->fill($personData);
            $user->status = User::STATUS_STEP_2;
            $user->save();

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'New user registration',
                'message' => "User person detail data successfully saved",
                'data' => $user->toArray()
            ], 200);
        } catch (Exception $e) {
            throw new UserRegistrationException($e);
        }
    }

    /**
     * Getting data about user
     *
     * @OA\Get(
     *     path="/users",
     *     summary="Getting data about user",
     *     description="Getting data about user",
     *     tags={"Users"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     x={
     *         "auth-type": "Application & Application User",
     *         "throttling-tier": "Unlimited",
     *         "wso2-application-security": {
     *             "security-types": {"oauth2"},
     *             "optional": "false"
     *         }
     *     },
     *
     *     @OA\Response(
     *          response="200",
     *          description="Detail data of user"
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="User not found"
     *     )
     * )
     */
    public function show(): JsonApiResponse
    {
        // Get object
        $user = $this->getObject(Auth::user()->getAuthIdentifier());

        if ($user instanceof JsonApiResponse) {
            return $user;
        }

        return response()->jsonApi([
            'type' => 'success',
            'title' => 'User details data',
            'message' => "User detail data has been received",
            'data' => $user->toArray()
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

    /**
     * User registration
     * Step 4. Saving acceptance agreement
     *
     * @OA\Patch(
     *     path="/users/agreement",
     *     summary="Saving acceptance agreement",
     *     description="Saving acceptance agreement",
     *     tags={"Users"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *     x={
     *         "auth-type": "Application & Application User",
     *         "throttling-tier": "Unlimited",
     *         "wso2-application-security": {
     *             "security-types": {"oauth2"},
     *             "optional": "false"
     *         }
     *     },
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="is_agreement",
     *                 type="boolean",
     *                 description="Email of contact",
     *                 example="true"
     *             )
     *         )
     *     ),
     *
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
     * @return mixed
     * @throws UserRegistrationException
     */
    public function agreement(Request $request): mixed
    {
        // Validate input
        try {
            $this->validate($request, [
                'is_agreement'
            ]);
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'User agreement data',
                'message' => "Validation error",
                'data' => $e->getMessage()
            ], 400);
        }

        // Try to save received data
        try {
            // Find Exist user
            $user = $this->getObject(Auth::user()->getAuthIdentifier());
            if ($user instanceof JsonApiResponse) {
                return $user;
            }

            $user->fill($request->all());
            $user->status = User::STATUS_STEP_4;
            $user->save();

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'New user registration',
                'message' => "User agreement set successfully",
                'data' => []
            ], 200);
        } catch (Exception $e) {
            throw new UserRegistrationException($e);
        }
    }
}
