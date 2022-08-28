<?php

namespace App\Services;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendVerifyToken
{
    /**
     * @param $instance
     * @param $to
     * @param $message
     * @return bool
     * @throws \Exception
     */
    public function dispatchOTP($instance, $to, $message): bool
    {
        $params = [
            'to' => $to,
            'message' => $message
        ];

        $url = $this->requestUrl($instance);
        $headers = $this->getHeaders();

        // Logging income data
        if(env('APP_DEBUG')){
            Log::info($url);
            Log::info($params);
        }

        try {
            $response = Http::withHeaders($headers)
                             ->post($url, $params);
        } catch (ConnectException $e) {
            throw $e;
        }

        return $response->successful();
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
        $host = config('settings.api.communications');

        return "{$host}/messages/{$instance}/send-message";
    }

    /**
     * @return string[]
     */
    public function getHeaders()
    {
        return [
            'user-id' => '10000000-1000-1000-1000-000000000001',
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ];
    }
}
