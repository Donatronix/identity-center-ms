<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class SendVerifyToken {
    
    /**
     * Generate request url
     * 
     * @param string $instance
     * 
     * @return string
     */
    public function requestUrl(string $instance):string
    {
        $host = env('MESSENGER_BASE_URL');
        $version = env('MESSENGER_VERSION');
         
        return "{$host}/{$version}/messages/{$instance}/send-message";
    }

    public function getHeaders()
    {
        return [
            'user-id' =>'10000000-1000-1000-1000-000000000001',
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ];
    }

    public function requestData($to, $message)
    {
        return [
            'to'=>$to,
            'message'=>$message
        ];
    }

    public function dispatchOTP($instance, $to, $message){
        $params = $this->requestData($to, $message);
        $url = $this->requestUrl($instance);
        $headers = $this->getHeaders();

        try {
            $response = Http::withHeaders($headers)->post($url, $params);
        } catch (Excection $e) {
            return false;
        }

        return $response->successful();
    }
}