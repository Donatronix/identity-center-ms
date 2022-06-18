<?php

namespace App\Api\V1\Controllers;

use App\Exceptions\ContributorRegistrationException;
use App\Http\Controllers\Controller;
use App\Models\Contributor;
use App\Services\IdentityVerification;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Sumra\SDK\JsonApiResponse;

/**
 * Class ContributorController
 *
 * @package App\Api\V1\Controllers
 */
class ContributorController extends Controller
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contributor::class;

    /**
     * ContributorController constructor.
     *
     * @param Contributor $model
     */
    public function __construct(Contributor $model)
    {
        $this->model = $model;
    }

    /**
     * Contributor registration
     * Step 2. Saving contributor person detail
     *
     * @OA\Post(
     *     path="/contributors",
     *     summary="Saving contributor person detail",
     *     description="Saving contributor person detail",
     *     tags={"Contributors"},
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
     *         @OA\JsonContent(ref="#/components/schemas/ContributorPerson")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Contributor created"
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
     * @throws ContributorRegistrationException
     */
    public function store(Request $request): mixed
    {
        // Validate input
        try {
            $this->validate($request, $this->model::personValidationRules());
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'Contributor person details data',
                'message' => "Validation error",
                'data' => $e->getMessage()
            ], 400);
        }

        // Try to save received data
        try {
            // Get user_id as contributor_Id
            $contributor_id = Auth::user()->getAuthIdentifier();

            // Find exist contributor
            $contributor = $this->model::find($contributor_id);

            // If not exist, then to create it
            if (!$contributor) {
                // Create new
                $contributor = $this->model::create([
                    'id' => $contributor_id,
                    'status' => Contributor::STATUS_STEP_1
                ]);
            }

            // Convert address field and save person data
            $personData = $request->all();
            foreach ($personData['address'] as $key => $value) {
                $personData['address_' . $key] = $value;
            }
            unset($personData['address']);

            $contributor->fill($personData);
            $contributor->status = Contributor::STATUS_STEP_2;
            $contributor->save();

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'New contributor registration',
                'message' => "Contributor person detail data successfully saved",
                'data' => $contributor->toArray()
            ], 200);
        } catch (Exception $e) {
            throw new ContributorRegistrationException($e);
        }
    }

    /**
     * Getting data about contributor
     *
     * @OA\Get(
     *     path="/contributors",
     *     summary="Getting data about contributor",
     *     description="Getting data about contributor",
     *     tags={"Contributors"},
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
     *          description="Detail data of contributor"
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="Contributor not found"
     *     )
     * )
     */
    public function show(): JsonApiResponse
    {
        // Get object
        $contributor = $this->getObject(Auth::user()->getAuthIdentifier());

        if ($contributor instanceof JsonApiResponse) {
            return $contributor;
        }

        return response()->jsonApi([
            'type' => 'success',
            'title' => 'Contributor details data',
            'message' => "Contributor detail data has been received",
            'data' => $contributor->toArray()
        ], 200);
    }

    /**
     * Contributor registration
     * Step 3.1. Saving contributor Identify data and Init verify session
     *
     * @OA\Post(
     *     path="/contributors/identify",
     *     summary="Saving contributor person detail",
     *     description="Saving contributor person detail",
     *     description="Document type (1 = PASSPORT, 2 = ID_CARD, 3 = DRIVERS_LICENSE, 4 = RESIDENCE_PERMIT)",
     *     tags={"Contributors"},
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
     *         description="Contributor created"
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
        // Get object
        $contributor = $this->getObject(Auth::user()->getAuthIdentifier());

        if ($contributor instanceof JsonApiResponse) {
            return $contributor;
        }

        // Init verify session
        $data = (new IdentityVerification())->startSession($contributor, $request);

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
     * Contributor registration
     * Step 3. Saving contributor Identify data
     *
     * @OA\Put(
     *     path="/contributors/identify",
     *     summary="Saving contributor identify data",
     *     description="Saving contributor identify data",
     *     tags={"Contributors"},
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
     *         @OA\JsonContent(ref="#/components/schemas/ContributorIdentify")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successfully save"
     *     ),
     *     @OA\Response(
     *         response="201",
     *         description="Contributor created"
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
     * @throws ContributorRegistrationException
     */
    public function update(Request $request): mixed
    {
        // Validate input
        try {
            $this->validate($request, $this->model::identifyValidationRules());
        } catch (ValidationException $e) {
            return response()->jsonApi([
                'type' => 'warning',
                'title' => 'Contributor identify data',
                'message' => "Validation error",
                'data' => $e->getMessage()
            ], 400);
        }

        // Try to save received document data
        try {
            // Find exist contributor
            $contributor = $this->getObject(Auth::user()->getAuthIdentifier());
            if ($contributor instanceof JsonApiResponse) {
                return $contributor;
            }

            // Transform data and save
            $identifyData = $request->all();
            foreach ($identifyData['document'] as $key => $value) {
                $identifyData['document_' . $key] = $value;
            }
            unset($identifyData['document']);

            $contributor->fill($identifyData);
            $contributor->status = Contributor::STATUS_STEP_3;
            $contributor->save();

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'New contributor registration',
                'message' => "Contributor Identify data successfully saved",
                'data' => []
            ], 200);
        } catch (Exception $e) {
            throw new ContributorRegistrationException($e);
        }
    }

    /**
     * Contributor registration
     * Step 4. Saving acceptance agreement
     *
     * @OA\Patch(
     *     path="/contributors/agreement",
     *     summary="Saving acceptance agreement",
     *     description="Saving acceptance agreement",
     *     tags={"Contributors"},
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
     *         description="Contributor created"
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
     * @throws ContributorRegistrationException
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
                'title' => 'Contributor agreement data',
                'message' => "Validation error",
                'data' => $e->getMessage()
            ], 400);
        }

        // Try to save received data
        try {
            // Find Exist contributor
            $contributor = $this->getObject(Auth::user()->getAuthIdentifier());
            if ($contributor instanceof JsonApiResponse) {
                return $contributor;
            }

            $contributor->fill($request->all());
            $contributor->status = Contributor::STATUS_STEP_4;
            $contributor->save();

            // Return response to client
            return response()->jsonApi([
                'type' => 'success',
                'title' => 'New contributor registration',
                'message' => "Contributor agreement set successfully",
                'data' => []
            ], 200);
        } catch (Exception $e) {
            throw new ContributorRegistrationException($e);
        }
    }

    /**
     * Get contributor object
     *
     * @param $id
     * @return mixed
     */
    private function getObject($id): mixed
    {
        try {
            return $this->model::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Get contributor",
                'message' => "Contributor with id #{$id} not found: {$e->getMessage()}",
                'data' => ''
            ], 404);
        }
    }
}
