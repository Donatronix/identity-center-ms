<?php

namespace App\Api\V1\Controllers\Webhooks;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
use App\Services\IdentityVerification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class IdentitiesWebhookController extends Controller
{

    /**
     * Get identities
     *
     * @OA\Post(
     *     path="/webhooks/identities",
     *     description="Webhooks Identify Notifications. Available object is: {events | decisions | sanctions}",
     *     summary="Webhooks Identify Notifications. Available object is: {events | decisions | sanctions}",
     *     tags={"Webhooks"},
     *
     *     security={{
     *         "default": {
     *             "ManagerRead",
     *             "User",
     *             "ManagerWrite"
     *         }
     *     }},
     *
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *     )
     * )
     *
     * @param Request $request
     * @param string $object
     *
     * @return mixed
     */
    public function __invoke(Request $request)
    {

        try {
            $id = $request->id;

            if (!is_array($id)) {
                $query = User::where('id', $id)->get();
            } else {
                $query = User::whereIn('id', $id)->get();
            }

            $data = empty($query) ? null : $query->toArray();

            return response()->jsonApi([
                'type' => 'success',
                'message' => 'Data received successfully',
                'data' => $data
            ]);
        } catch
        (Throwable $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Get identity",
                'message' => $e->getMessage(),
                'data' => ''
            ], 404);
        }

    }
}
