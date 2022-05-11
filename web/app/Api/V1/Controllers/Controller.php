<?php

namespace App\Api\V1\Controllers;

use App\Exceptions\SMSGatewayException;
use Exception;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title=SWAGGER_TITLE,
 *     description=SWAGGER_DESCRIPTION,
 *     version=SWAGGER_VERSION,
 *
 *     @OA\Contact(
 *         email=SWAGGER_SUPPORT_EMAILS,
 *         name="Support Team"
 *     )
 * )
 */

/**
 * @OA\Server(
 *      url=SWAGGER_CONST_HOST,
 *      description=SWAGGER_DESCRIPTION
 * )
 */

/**
 * @OA\SecurityScheme(
 *     type="oauth2",
 *     description="Auth Scheme",
 *     name="oAuth2 Access",
 *     securityScheme="default",
 *
 *     @OA\Flow(
 *         flow="implicit",
 *         authorizationUrl="https://sumraid.com/oauth2",
 *         scopes={
 *             "ManagerRead"="Manager can read",
 *             "User":"User access",
 *             "ManagerWrite":"Manager can write"
 *         }
 *     )
 * )
 */

/**
* @OA\SecurityScheme(
*      in="header",
*      type="http",
*      scheme="bearer",
*      name="bearerAuth",
*      bearerFormat="JWT",
*      securityScheme="bearerAuth",
* ),
*/

/**
 * Api Base Class Controller
 *
 * @package App\Api\V1\Controllers
 */
class Controller extends BaseController
{
    protected function sendSms($botID, $phoneNumber, $message)
    {
        try {
            //  api call to communication MS
        } catch (Exception $th) {
            throw new SMSGatewayException("Unable to send sms");
        }

        return Str::random(16);
    }
}

