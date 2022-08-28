<?php

namespace App\Services;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Http;

class SendEmailNotify
{
    public function dispatchEmail($to, $subject, $message, $template): bool
    {
        $params = [
            'subject' => $subject,
            'body' => $message,
            'emails' => $to,
            'template' => $template,
        ];

        $url = $this->requestUrl();
        $headers = $this->getHeaders();

        try {
            $response = Http::withHeaders($headers)->post($url, $params);
        } catch (ConnectException $e) {
            throw $e;
        }

        return $response->successful();
    }

    /**
     * Generate request url
     *
     * @return string
     */
    public function requestUrl(): string
    {
        $host = config('settings.api.communications');

        return "{$host}/mail/sender";
    }

    public function getHeaders()
    {
        return [
            'user-id' => '10000000-1000-1000-1000-000000000001',
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ];
    }
}
