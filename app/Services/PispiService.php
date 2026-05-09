<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PispiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.pispi.base_url');
    }

    /**
     * Initiate a payment with PI-SPI
     */
    public function initiatePayment(array $payload): array
    {
        $url = $this->baseUrl . '/payments'; // chemin exemple
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-KEY'     => config('services.pispi.api_key'),
        ])->post($url, $payload);

        return $response->json();
    }

    /**
     * Query payment status
     */
    public function queryPayment(string $reference): array
    {
        $url = $this->baseUrl . '/payments/' . $reference;
        $resp = Http::withHeaders([
            'X-API-KEY' => config('services.pispi.api_key'),
        ])->get($url);

        return $resp->json();
    }

}
