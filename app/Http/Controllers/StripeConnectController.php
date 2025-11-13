<?php

// app/Http/Controllers/StripeConnectController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\ConnectedAccount;
use App\Models\MobileSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Account;

class StripeConnectController extends Controller
{
    public function authorize(Request $req)
    {
        // The client passes redirect_uri (mobile app redirect) to our backend
        $client_id = env('STRIPE_CLIENT_ID');
        $stripeAuthUrl = 'https://connect.stripe.com/oauth/authorize';

        $state = Str::random(24);
        // 
        $req->session()->put('stripe_oauth_state', $state);
        // keep mobile redirect in session to use later
        $mobileRedirect = $req->query('redirect_uri', env('FRONTEND_REDIRECT_SCHEME'));
        Log::info('redur',[$req->query('redirect_uri'), env('FRONTEND_REDIRECT_SCHEME')]);
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $client_id,
            'scope' => 'read_write',
            'state' => $state,
            // we ask Stripe to redirect back to our backend callback
            'redirect_uri' => env('APP_URL') . '/connect/callback',
            // optionally prefill or show dialog
            'suggested_capabilities' => ['card_payments', 'transfers'],
            'stripe_user' => [
                'country' => 'US'
            ],
        ]);

        // store mobileRedirect so callback can use it
        $req->session()->put('mobile_redirect', $mobileRedirect);

        return redirect($stripeAuthUrl . '?' . $params);
    }

    public function callback(Request $req)
    {
        // verify state
        // $state = $req->query('state');
        // if (!$state || $state !== $req->session()->get('stripe_oauth_state')) {
        //     return response('Invalid state', 400);
        // }

        $code = $req->query('code');
        if (!$code) {
            return response('No code returned', 400);
        }
        // echo env('STRIPE_SECRET');die;
        // Exchange code for tokens
        $resp = Http::asForm()->post('https://connect.stripe.com/oauth/token', [
            'client_secret' => env('STRIPE_SECRET'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if ($resp->failed()) {
            \Log::error('Stripe OAuth Token Exchange failed', ['response' => $resp->body()]);
            return response('Token exchange failed', 500);
        }

        $data = $resp->json();
        // Data sample: access_token, refresh_token, stripe_user_id, scope, livemode

        // Save or update connected account
        $account = ConnectedAccount::updateOrCreate(
            ['stripe_user_id' => $data['stripe_user_id']],
            [
                'access_token' => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'scope' => $data['scope'] ?? null,
                'livemode' => $data['livemode'] ?? null,
                'raw_response' => json_encode($data),
            ]
        );

        // Create a one-time session id to return to mobile safely
        $sessionId = Str::random(40);
        // Optionally create a session_token for longer use — here we create a simple token
        $sessionToken = Str::random(64);

        $ms = MobileSession::create([
            'session_id' => $sessionId,
            'connected_account_id' => $account->id,
            'session_token' => $sessionToken,
            'expires_at' => Carbon::now()->addDays(30) // short lived
        ]);

        // Redirect to mobile app deep link (redirect was saved in session earlier)
        $mobileRedirect = $req->session()->get('mobile_redirect', env('FRONTEND_REDIRECT_SCHEME'));
        $state = isset($state)?$state:'';
        \Log::error('mrd', ['response' => $mobileRedirect]);
        // Append session id (safe), avoid sending secret tokens in URL
        $redirectTo = $mobileRedirect . '?session_id=' . urlencode($sessionId) . '&state=' . urlencode($state);

        return redirect($redirectTo);
    }

    // Mobile will call this to exchange session_id for session_token & account id
    public function mobileSession(Request $req, $session_id)
    {
        $ms = MobileSession::where('session_id', $session_id)
            ->where('expires_at', '>', now())
            ->first();

        if (!$ms) {
            return response()->json(['error' => 'invalid_or_expired_session'], 404);
        }

        $account = $ms->account;
        return response()->json([
            'session_token' => $ms->session_token,
            'connected_account_id' => $account->stripe_user_id,
            'account_info' => [
                'stripe_user_id' => $account->stripe_user_id,
                // optionally include more public info
            ],
        ]);
    }

    // Example endpoint to view connected account info (authenticated by session token later)
    // You should secure this endpoint with your own middleware that verifies session_token
    public function me(Request $req)
    {
        $sessionToken = $req->header('X-App-Session-Token'); // or Authorization: Bearer <token>
        $ms = MobileSession::where('session_token', $sessionToken)
            ->where('expires_at', '>', now())
            ->first();

        if (!$ms) return response()->json(['error' => 'unauthorized'], 401);

        $acc = $ms->account;
        // Return stored raw_response or call Stripe for more details using access_token
        // return response()->json([
        //     'stripe_user_id' => $acc->stripe_user_id,
        //     'raw' => json_decode($acc->raw_response ?? '{}'),
        // ]);

        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $account = Account::retrieve($acc->stripe_user_id);

            return response()->json([
                'id' => $account->id,
                'business_name' => $account->business_profile->name ?? $account->settings->dashboard->display_name ?? null,
                'email' => $account->email,
                'type' => $account->type,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

