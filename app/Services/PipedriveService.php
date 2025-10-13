<?php

namespace App\Services;

use App\Models\PipedriveToken;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PipedriveService
{
    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()->post('https://oauth.pipedrive.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => '0fc46d8353d41dd9',
            'client_secret' => '871fb36b04ab88d8fef1b9295a9f0a6672d423df',
            'redirect_uri' => 'https://pipedrive-3jeqg.sevalla.app/oauth/callback',
        ]);

        $tokens = $response->json();
        return $tokens;
    }

    public function getUserInfo(string $accessToken, string $apiDomain): array
    {
        $response = Http::withToken($accessToken)->get($apiDomain . '/v1/users/me');
        return $response->json();
    }

    public function saveTokens(array $tokens, ?int $companyId): void
    {
        PipedriveToken::updateOrCreate(
            ['company_id' => $companyId],
            [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_at' => Carbon::now()->addSeconds($tokens['expires_in']),
            ]
        );
    }

    public function getContactEmail(int $companyId, int $personId): ?string
    {
        $token = PipedriveToken::where('company_id', $companyId)->first();
        if (!$token) {Log::info('nottoken:', [$token]);
            return null;
        }

        $response = Http::withToken($token->access_token)
            ->get("https://api.pipedrive.com/v1/persons/{$personId}");

        if ($response->failed()) {
        Log::info('failed:', [$token->access_token]);
            return null;
        }

        $contact = $response->json();
        return $contact['data']['email'][0]['value'] ?? null;
    }
}
