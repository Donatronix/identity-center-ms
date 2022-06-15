<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class SendEmailNotify {
    
    /**
     * Generate request url
     * 
     * @return string
     */
    public function requestUrl():string
    {
        $host = env('MESSENGER_BASE_URL');
        $version = env('MESSENGER_VERSION');
         
        return "{$host}/{$version}/mail";
    }

    public function getHeaders()
    {
        return [
            'user-id' =>'10000000-1000-1000-1000-000000000001',
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ];
    }

    public function requestData($to, $subject, $message)
    {
        return [
            'emails'=>$to,
            'subject'=>$to,
            'body'=>$message
        ];
    }

    public function dispatchEmail($to, $subject, $message){
        $params = $this->requestData($to, $subject, $message);
        $url = $this->requestUrl();
        $headers = $this->getHeaders();

        try {
            $response = Http::withHeaders($headers)->post($url, $params);
        } catch (Excection $e) {
            return false;
        }

        return $response->successful();
    }
}