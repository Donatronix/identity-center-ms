<?php

namespace App\Api\V1\Controllers;

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
 *     name="OneStep oAuth2 Access",
 *     securityScheme="passport",
 *
 *     @OA\Flow(
 *         flow="password",
 *         tokenUrl= "http://localhost:8200/v1/auth/token",
 *         refreshUrl="http://localhost:8200/v1/auth/token",
 *         scopes={
 *             "Super Admin": "Super Admin access",
 *             "Admin": "Admin access",
 *             "Investor": "User access",
 *             "Staff": "Staff access",
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
 *      description="Auth Token",
 *      securityScheme="bearerAuth",
 * ),
 */

/**
 * Success Response
 *
 * @package App\Api\V1\Controllers
 *
 * @OA\Schema(
 *      schema="OkResponse",
 *
 *      @OA\Property(
 *          property="type",
 *          type="string",
 *          example="success"
 *      ),
 *      @OA\Property(
 *          property="title",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="message",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="data",
 *          type="object"
 *      ),
 * )
 */

/**
 * Warning Response
 *
 * @package App\Api\V1\Controllers
 *
 * @OA\Schema(
 *      schema="InfoResponse",
 *
 *      @OA\Property(
 *          property="type",
 *          type="string",
 *          example="info"
 *      ),
 *      @OA\Property(
 *          property="title",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="message",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="data",
 *          type="object"
 *      ),
 * )
 */

/**
 * Warning Response
 *
 * @package App\Api\V1\Controllers
 *
 * @OA\Schema(
 *      schema="WarningResponse",
 *
 *      @OA\Property(
 *          property="type",
 *          type="string",
 *          example="warning"
 *      ),
 *      @OA\Property(
 *          property="title",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="message",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="data",
 *          type="object"
 *      ),
 * )
 */

/**
 * Danger Response
 *
 * @package App\Api\V1\Controllers
 *
 * @OA\Schema(
 *      schema="DangerResponse",
 *
 *      @OA\Property(
 *          property="type",
 *          type="string",
 *          example="danger"
 *      ),
 *      @OA\Property(
 *          property="title",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="message",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="data",
 *          type="object"
 *      ),
 * )
 */

/**
 * Api Base Class Controller
 *
 * @package App\Api\V1\Controllers
 */
class Controller extends BaseController {}
