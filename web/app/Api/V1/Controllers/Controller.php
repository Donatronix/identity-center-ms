<?php

namespace App\Api\V1\Controllers;

use Exception;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Global Identity Centre. Sumra ID API",
 *     description="This is API of Global Identity Centre / Sumra ID",
 *     version="V1",
 *
 *     @OA\Contact(
 *         email="admin@sumraid.com",
 *         name="Global Identity Centre Support Team"
 *     )
 * )
 */

/**
 *  @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Global Identity Centre. Sumra ID API, Version 1"
 *  )
 */

/**
 * Api Base Class Controller
 *
 * @package App\Api\V1\Controllers
 */
class Controller extends BaseController
{

    protected function sendSms($botID, $phoneNumber, $message){
          
          try {
             
            // contact communication MS 
            


          } catch (Exception $th) {

              throw new SMSGatewayException("Unable to send sms");
          }

          return Str::random(16);
    }
  
}

