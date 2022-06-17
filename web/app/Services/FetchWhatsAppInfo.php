<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\ConnectException;


class FetchWhatsAppInfo {
    
    /**
     * Generate request url
     * 
     * @return string
     */
    public function requestUrl():string
    {
        $phoneNumber = env('FROM_PHONE_NUMBER');
        $baseUrl = 'https://graph.facebook.com';
        $version = 'v14.0';
         
        return "{$baseUrl}/{$version}/{$phoneNumber}/messages";
    }

    public function getHeaders()
    {
        return [
            'user-id' =>'10000000-1000-1000-1000-000000000001',
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Authorization' => env('WHATSAPP_TOKEN')
        ];
    }

    public function requestData($phone_number)
    {
        return [
            'messaging_product'=>'whatsapp',
            'to'=>$phone_number,
            'type'=> 'template',
            'template'=>[
                'name'=>'Test chat',
                'language'=>[
                    'code'=>'en_US'
                ]
            ],
        ];
    }

    public function sendTestChat($phone_number){
        $params = $this->requestData($phone_number);
        $url = $this->requestUrl();
        $headers = $this->getHeaders();

        try {
            $response = Http::withHeaders($headers)->post($url, $params);
        } catch (Excection $e) {
            return false;
        }catch (ConnectException $e) {
            return false;
        }

        return $response->successful();
    }
}