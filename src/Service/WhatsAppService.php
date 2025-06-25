<?php

namespace App\Service;


use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use UltraMsg\WhatsAppApi;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class WhatsAppService
{
    private ?String $token = "wxlevixcyul6r17q" ;
    private ?String $instance_id = "instance112771";

    // Waapi token
    private ?string $waapiToken = 'I5BnUF1NpK2dz5Vq6TGiUAZeLsZ1oPKUwvgPEOg557d3a4fa';
    private ?string $waapiInstanceId = '75848';

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

    // Waapi service
    public function sendMessageUsingWaapi(string $to, string $body): void
    {
        $url = sprintf(
            'https://waapi.app/api/v1/instances/%s/client/action/send-message',
            $this->waapiInstanceId
        );

        $payload = [
            'chatId' => $to . '@c.us',
            'message' => $body,
        ];

        $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->waapiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);





    }
}
