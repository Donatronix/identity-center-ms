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
     *     path="/webhooks/identify/{type}",
     *     description="Webhooks Identify Notifications. Available type is: {events | decisions | sanctions}",
     *     summary="Webhooks Identify Notifications. Available type is: {events | decisions | sanctions}",
     *     tags={"Webhooks"},
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
     *     @OA\Parameter(
     *         name="type",
     *         description="Webhook type: {events | decisions | sanctions}",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *              default="events"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *     )
     * )
     *
     * @param Request $request
     * @param string $type
     *
     * @return mixed
     */
    public function __invoke(string $type, Request $request): mixed
    {
        // Set logging
        if (env("APP_DEBUG", 0)) {
            Log::info("Type: {$type}");
        }

        // Handle Webhook data
        $result = (new IdentityVerification())->handleWebhook($type, $request);

        if($result->type == 'danger'){
            return response()->jsonApi([
                'type' => $result->type,
                'message' => $result->message,
                'data' => []
            ], $result->code);
        }

        try {
            $contributor = Contributor::find($result->contributor_id);
            $contributor->is_verified = true;
            $contributor->save();

        } catch (ModelNotFoundException $e) {
            return response()->jsonApi([
                'type' => 'danger',
                'title' => "Get contributor",
                'message' => "Contributor with id #{$result->contributor_id} not found: {$e->getMessage()}",
                'data' => ''
            ], 404);
        }

        // Send status 200 OK
        return response('');
    }

//    public function webhookEvents(Request $request){
//        (new IdentityVerification())->handleWebhook('events', $request);
//    }
//
//    public function webhookNotifications(Request $request){
//        (new IdentityVerification())->handleWebhook('notifications', $request);
//    }
//
}
