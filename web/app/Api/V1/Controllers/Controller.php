<?php

namespace App\Api\V1\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Identity Server API",
 *     description="This is API of Identity Server",
 *     version="V1",
 *
 *     @OA\Contact(
 *         email="admin@sumraid.com",
 *         name="SumraID Support Team"
 *     )
 * )
 */

/**
 *  @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Identity Server API, Version 1"
 *  )
 */

/**
 * Api Base Class Controller
 *
 * @package App\Api\V1\Controllers
 */
class Controller extends BaseController
{
    use ValidatesRequests;
}
