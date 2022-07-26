<?php

namespace App\Models;

use Sumra\SDK\Traits\UuidTrait;
use Laravel\Passport\Client as PassportClient;

class Client extends PassportClient
{
    use UuidTrait;
}
