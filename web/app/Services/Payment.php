<?php

namespace App\Services;

use Illuminate\Support\Str;

class Payment
{
    /**
     * @param $gateway
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public static function getInstance($gateway)
    {
        try{
            $class = '\App\Services\Payments\\' . Str::ucfirst($gateway) . 'Manager';
            $reflector = new \ReflectionClass($class);
        } catch(\Exception $e){
            throw $e;
        }

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Payment gateway [$class] is not instantiable.");
        }

        if($reflector->getProperty('gateway') === null){
            throw new \Exception("Can't init gateway [$gateway].");
        }

        return $reflector->newInstance();
    }
}
