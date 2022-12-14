<?php

namespace App\Services;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Http;

class FetchWhatsAppInfo
{
    public function sendTestChat($phone_number)
    {
        $params = $this->requestData($phone_number);
        $url = $this->requestUrl();
        $headers = $this->getHeaders();

        try {
            $response = Http::withHeaders($headers)->post($url, $params);
        } catch (Excection $e) {
            return false;
        } catch (ConnectException $e) {
            return false;
        }

        return $response->successful();
    }

    public function requestData($phone_number)
    {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $phone_number,
            'type' => 'template',
            'template' => [
                'name' => 'Test chat',
                'language' => [
                    'code' => 'en_US'
                ]
            ],
        ];
    }

    /**
     * Generate request url
     *
     * @return string
     */
    public function requestUrl(): string
    {
        $phoneNumber = env('FROM_PHONE_NUMBER');
        $baseUrl = 'https://graph.facebook.com';
        $version = 'v14.0';

        return "{$baseUrl}/{$version}/{$phoneNumber}/messages";
    }

    public function getHeaders()
    {
        return [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Authorization' => 'Bearer' . env('WHATSAPP_TOKEN')
        ];
    }
}
