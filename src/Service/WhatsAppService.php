<?php

namespace App\Service;


use Symfony\Contracts\HttpClient\HttpClientInterface;
use UltraMsg\WhatsAppApi;

class WhatsAppService
{
    private ?String $token = "wxlevixcyul6r17q" ;
    private ?String $instance_id = "instance112771";

    private string $apiUrl;
    public function __construct(
        private HttpClientInterface $client,
    )
    {}



    public function sendMessage (string $to, string $body, string $referenceId = "")
    {
        $client = new WhatsAppApi($this->token, $this->instance_id);
        $messageBody = $body;
        return $client->sendChatMessage($to, $messageBody, 5, $referenceId);
    }










}
