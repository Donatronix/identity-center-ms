<?php
namespace App\Exceptions;

use Exception;



class SMSGatewayException extends Exception {


    public function __construct($message = "Unable to send sms.")
    {   

        parent::__construct($message);
        
    }
  
}