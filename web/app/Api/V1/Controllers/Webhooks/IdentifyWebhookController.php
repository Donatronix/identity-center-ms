<?php

namespace App\Api\V1\Controllers\Webhooks;

use App\Api\V1\Controllers\Controller;
use App\Models\User;
use App\Services\IdentityVerification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdentifyWebhookController extends Controller
{
    /**
     * Identify webhook
     *
     * @OA\Post(
     *     path="/webhooks/identify/{object}",
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
     *     @OA\Parameter(
     *         name="object",
     *         description="Webhook object: {events | decisions | sanctions}",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *              default="events"
     *         )
     *     ),
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
    public function __invoke(string $object, Request $request): mixed
    {
        // Set logging
        if (env("APP_DEBUG", 0)) {
            Log::info("Object: {$object}");
        }

        // Handle Webhook data
        $result = (new IdentityVerification())->handleWebhook($object, $request);

        if ($result->type == 'danger') {
            return response()->jsonApi([
                'type' => $result->type,
                'message' => $result->message,
                'data' => []
            ], $result->code);
        }

        try {
            $user = User::find($result->user_id);
            $user->is_kyc_verified = true;
            $user->save();

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Get user",
                'message' => "User with id #{$result->user_id} not found: {$e->getMessage()}",
                'data' => ''
            ], 404);
        }

        // Send status 200 OK
        return response('');
    }
}
