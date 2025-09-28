<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class StripeService
{
    private $baseUrl = 'https://octopus-app-3hac5.ondigitalocean.app/api/stripe_data';

    public function getTransactionsByEmail(string $email): ?array
    {
        $response = Http::get($this->baseUrl, ['email' => $email]);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }
}
