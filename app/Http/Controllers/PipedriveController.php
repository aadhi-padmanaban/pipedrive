<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\PipedriveToken;
use Carbon\Carbon;

class PipedriveController extends Controller
{
    public function oauthCallback(Request $request)
    {
        try {
            $code = $request->query('code');

            $response = Http::asForm()->post('https://oauth.pipedrive.com/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => env('PD_CLIENT_ID'),
                'client_secret' => env('PD_CLIENT_SECRET'),
                'redirect_uri' => env('PD_REDIRECT_URI'),
            ]);

            $tokens = $response->json();



            $response = Http::withToken($tokens['access_token'])
                ->get($tokens['api_domain'].'/v1/users/me');

            $userInfo = $response->json();
            $companyId = $userInfo['data']['company_id'] ?? null; // Adjust based on actual field returned

            PipedriveToken::updateOrCreate(
                ['company_id' => $companyId],
                [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_at'   => Carbon::now()->addSeconds($tokens['expires_in']),
                ]
            );

            return redirect($tokens['api_domain']);
        } catch (\Exception $e) {
            \Log::error('OAuth callback error: ' . $e->getMessage());
            return response()->json(['error' => 'OAuth callback failed, please try again.'], 500);
        }
        
    }

    public function panel(Request $request)
    {
        \Log::info('Request input:', $request->all());
        // Loads iframe panel UI
        return view('panel');
    }
    


    public function transactions(Request $request)
    {
        
        $companyId = $request->query('companyId');
        $personId = $request->query('personId');
        $email = $this->getContactEmail($companyId, $personId);
        

        if (!$email) {
            return response()->json(['error' => 'Email required'], 400);
        }

        $response = Http::get("https://octopus-app-3hac5.ondigitalocean.app/api/stripe_data", [
            'email' => $email
        ]);
        
        if ($response->failed()) {
            return response()->json($response->json(),$response->status());
        }

        $data = $response->json();

        // Transform invoices + charges into single array
        $invoices = [];
        $charges = [];

        foreach ($data['invoices'] ?? [] as $inv) {
            $invoices[] = [
                'id' => $inv['number'],
                'type' => 'Invoice',
                'amount' => $inv['amount_due'] / 100,
                'status' => $inv['status'],
                'customer' => $inv['customer'],
                'date' => date('Y-m-d H:i:s', $inv['created']),
                'receipt' => $inv['hosted_invoice_url'] ?? null,
            ];
        }

        foreach ($data['charges'] ?? [] as $ch) {
            $charges[] = [
                'id' => $ch['id'],
                'type' => 'Charge',
                'amount' => $ch['amount'] / 100,
                'status' => $ch['status'],
                'customer' => $ch['customer'],
                'date' => date('Y-m-d H:i:s', $ch['created']),
                'receipt' => null,
            ];
        }
        
        $resp = ['transactions'=>[
            'invoices'=>$invoices,
            'charges'=>$charges
            ]
        ];

        return response()->json($resp)
         ->header('X-Frame-Options', 'ALLOW-FROM https://*.pipedrive.com')
                 ->header('Content-Security-Policy', "frame-ancestors https://*.pipedrive.com");
    }

    public function getContactEmail($companyId, $personId)
    {
        $token = PipedriveToken::where('company_id', $companyId)->first();
        
        if (!$token) return null;

        $response = Http::withToken($token->access_token)
            ->get("https://api.pipedrive.com/v1/persons/{$personId}");
        
        if ($response->failed()) {
            return null;
        }

        $contact = $response->json();
        
        return $contact['data']['email'][0]['value'] ?? null;
    }

}

