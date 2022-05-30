<?php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class KYCService {

    protected $client;
    protected $base_url;
    protected $public_key;
    protected $private_key;


    public function __construct() {
        $this->base_url = config('identity.veriff.base_url');
        $this->client = new Client([
            'base_uri' => config('identity.veriff.base_url'),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-auth-client' => config('identity.veriff.public_key')
            ],
            'timeout' => 40
        ]);
    }

    public function createUser($data){
        try{
            $response = $this->client->post($this->base_url . '/v1/createUser.do', [

            ]); 
            return $response->getBody();    
        }catch(ClientException $e){
            $response = $e->getResponse();
            return $response->getBody();
        }

        
    }

    public function eKCY(Array $data, Array $kycUserDetails, $merchantDetails=[]){
        try{
            $response = $this->client->post($this->base_url . '/v1/eKCY.do', [
                "merchantId"=> $data['merchantId'],
                "merchantSiteId"=> $data['merchantSiteId'],
                "userTokenId"=> $data['userTokenId'],
                "userId"=> $data['userId'],
                "clientUniqueId"=> $data['clientUniqueId'],
                "clientRequestId"=> $data['clientRequestId'],
                "userDetails"=> $userDetails,
                "kycUserDetails"=> $kycUserDetails,
                "merchantDetails"=> $merchantDetails,
                "urlDetails"=> [
                    "notificationUrl"=> $data['notificationUrl'],
                ],
                "timeStamp"=> $data['timeStamp'],
                "checksum"=> $data['checksum'],
            
            ]);
            return $response->getBody();
        }catch(ClientException $e){
            $response = $e->getResponse();
            return $response->getBody();
        }
    }

    public function documentUploadUrl($data, $kycUserDetails, $merchantDetails=[]){
        try{
            $response = $this->client->post($this->base_url . '/v1/documentUploadUrl.do', [
                "merchantId"=> $data['merchantId'],
                "merchantSiteId"=> $data['merchantSiteId'],
                "userTokenId"=> $data['userTokenId'],
                "userId"=> $data['userId'],
                "clientUniqueId"=> $data['clientUniqueId'],
                "clientRequestId"=> $data['clientRequestId'],
                "userDetails"=> $userDetails,
                "kycUserDetails"=> $kycUserDetails,
                "merchantDetails"=> $merchantDetails,
                "urlDetails"=> [
                    "notificationUrl"=> $data['notificationUrl'],
                ],
                "timeStamp"=> $data['timeStamp'],
                "checksum"=> $data['checksum'],
            
            ]);
            return $response->getBody();
        }catch(ClientException $e){
            $response = $e->getResponse();
            return $response->getBody();
        }
    }
}