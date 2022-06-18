<?php

namespace App\Services;

use App\Models\Identification;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;

class IdentityVerification
{
    /**
     * @var Client
     */
    protected Client $client;

    protected $document_types = [
        1 => 'PASSPORT',
        2 => 'ID_CARD',
        3 => 'DRIVERS_LICENSE',
        4 => 'RESIDENCE_PERMIT'
    ];

    /**
     * IdentityVerification constructor.
     */
    public function __construct()
    {
        // Setup client configuration
        $clientConfig = [
            'base_uri' => config('identity.veriff.base_url'),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-auth-client' => config('identity.veriff.public_key')
            ],
            'timeout' => 40
        ];

        // Set debug info
        if (env('APP_DEBUG', 0)) {
            $stack = HandlerStack::create();
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
                Log::info("### Veriff KYC Request:");
                Log::info("URI: {$request->getUri()}");
                Log::info('Headers: ', $request->getHeaders());
                Log::info('Request: ', json_decode($request->getBody(), true));

                return $request;
            }));

            $clientConfig['handler'] = $stack;
        }

        // Init HTTP Client
        $this->client = new Client($clientConfig);
    }

    /**
     * Make request to veriff API to verify identity
     *
     * @param User $user
     * @param Request $request
     * @return mixed|object
     */
    public function startSession(User $user, Request $request): mixed
    {
        $body = [
            'json' => [
                'verification' => [
                    'callback' => url('/user-profile/identify-webhook'),
                    'person' => [
                        'firstName' => $user->first_name,
                        'lastName' => $user->last_name,
                    ],
                    'document' => [
                        'type' => $this->document_types[$request->get('document_type')],
                        'country' => $user->address_country
                    ],
                    'vendorData' => json_encode([
                        'user_id' => $user->id
                    ]),
                    'timestamp' => Carbon::now()
                ]
            ]
        ];

        try {
            $response = $this->client->request('POST', '/v1/sessions/', $body);
            $data = json_decode($response->getBody());

            return (object)[
                'status' => $data->status,
                'verification' => [
                    'sessionUrl' => $data->verification->url,
                    'sessionToken' => $data->verification->sessionToken
                ]
            ];
        } catch (ClientException $e) {
            return json_decode($e->getResponse()->getBody());
        }
    }

    /**
     * @param $type
     * @param Request $request
     * @return mixed
     */
    public function handleWebhook($type, Request $request): mixed
    {
        // Get request headers
        $headers = $request->headers;

        // Set logging headers
        if (env("APP_DEBUG", 0)) {
            Log::info("Headers:\n{$headers}");
        }

        // Check x-auth-client, some as public_key
        if (!$headers->has('x-auth-client') || ($headers->get('x-auth-client') !== config('identity.veriff.public_key'))) {
            return (object)[
                'type' => 'danger',
                'message' => 'Missing or Incorrect Public Key',
                'code' => 401
            ];
        }

        // Check if exist x-hmac-signature
        if (!$headers->has('x-hmac-signature')) {
            return (object)[
                'type' => 'danger',
                'message' => 'Missing HMAC Signature',
                'code' => 401
            ];
        }

        // Get request data
        $payload = $request->all();

        // Generate signature hash by HMAC-SHA256
        $signature = strtolower(hash_hmac('sha256', json_encode($payload), config('identity.veriff.private_key')));

        // Set logging generated signature
        if (env("APP_DEBUG", 0)) {
            Log::info("Signature: {$signature}");
        }

        // Check if exist x-hmac-signature
        if ($headers->has('x-hmac-signature') !== $signature) {
            //    return (object)[
            //        'type' => 'danger',
            //        'message' => 'Signatures is different',
            //        'code' => 401
            //    ];
        }

        // Set logging generated signature
        if (env("APP_DEBUG", 0)) {
            Log::info("Request: ", $payload);
        }

        // Get vendor Data from request
        $vendorData = json_decode($payload['verification']['vendorData']);

        // Save Veriff data
        Identification::create([
            'session_id' => $payload['verification']['id'],
            'user_id' => $vendorData->user_id,
            'status' => $payload['verification']['code'],
            'payload' => $payload
        ]);

        return (object)[
            'type' => 'success',
            'contributor_id' => $vendorData->user_id,
        ];
    }
}
