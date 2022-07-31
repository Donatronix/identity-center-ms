<?php

namespace App\Exceptions;

use Exception;

class CommunicationChannelsException extends Exception
{
    public function __construct($message = "Unable to send message")
    {
        parent::__construct($message);
    }
}
