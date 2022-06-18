<?php

namespace App\Services;

use App\Exceptions\SMSGatewayException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendVerifyToken
{
    public function dispatchOTP($instance, $to, $message)
    {
        $params = $this->requestData($to, $message);
        $url = $this->requestUrl($instance);
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

    public function requestData($to, $message)
    {
        return [
            'to' => $to,
            'message' => $message
        ];
    }

    /**
     * Generate request url
     *
     * @param string $instance
     *
     * @return string
     */
    public function requestUrl(string $instance): string
    {
        $host = env('MESSENGER_BASE_URL');
        $version = env('MESSENGER_VERSION');

        return "{$host}/{$version}/messages/{$instance}/send-message";
    }

    public function getHeaders()
    {
        return [
            'user-id' => '10000000-1000-1000-1000-000000000001',
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ];
    }

    protected function sendSms($botID, $phoneNumber, $message)
    {
        try {
            //  api call to communication MS
        } catch (Exception $th) {
            throw new SMSGatewayException("Unable to send sms");
        }

        return Str::random(16);
    }
}
