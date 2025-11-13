<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConnectedAccount;
use App\Models\MobileSession;
use Illuminate\Support\Facades\Http;
use Stripe\Stripe;
use Stripe\Terminal\ConnectionToken;
use Stripe\PaymentIntent;
use Carbon\Carbon;

class TerminalController extends Controller
{
    // Return a connection token secret for the SDK to initialize
    public function connectionToken(Request $req)
    {\Log::error('mobtsdfok', ['response' => $req]);
        // Authenticate mobile session
        $sessionToken = $req->header('X-App-Session-Token');
        if (!$sessionToken) {
            return response()->json(['error' => 'missing_session'], 401);
        }
        \Log::error('mobtok', ['response' => $sessionToken]);
        $ms = MobileSession::where('session_token', $sessionToken)
            ->where('expires_at', '>', now())
            ->first();

        if (!$ms) return response()->json(['error'=>'invalid_session'], 401);

        $account = $ms->account; // connected account model
        if (!$account) return response()->json(['error'=>'no_connected_account'], 404);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Create connection token on behalf of connected account
        try {
            $ct = ConnectionToken::create(
                [], 
                ['stripe_account' => $account->stripe_user_id]
            );

            return response()->json(['secret' => $ct->secret]);
        } catch (\Exception $e) {
            \Log::error('ConnectionToken error: '.$e->getMessage());
            return response()->json(['error'=>'connection_token_failed'], 500);
        }
    }
    public function xx(Request $req)
    {\Log::error('mobtsdfok', ['response' => $req]);
        // Authenticate mobile session
        $sessionToken = $req->query('tok');
        if (!$sessionToken) {
            return response()->json(['error' => 'missing_session'], 401);
        }
        \Log::error('mobtok', ['response' => $sessionToken]);
        $ms = MobileSession::where('session_token', $sessionToken)
            ->where('expires_at', '>', now())
            ->first();

        if (!$ms) return response()->json(['error'=>'invalid_session'], 401);

        $account = $ms->account; // connected account model
        if (!$account) return response()->json(['error'=>'no_connected_account'], 404);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Create connection token on behalf of connected account
        try {
            $ct = ConnectionToken::create(
                [], 
                ['stripe_account' => $account->stripe_user_id]
            );

            return response()->json(['secret' => $ct->secret]);
        } catch (\Exception $e) {
            \Log::error('ConnectionToken error: '.$e->getMessage());
            return response()->json(['error'=>'connection_token_failed'], 500);
        }
    }

    // Create a PaymentIntent (server-driven). Expects amount in smallest currency unit (e.g., paisa)
    public function createPaymentIntent(Request $req)
    {\Log::error('payintent', ['response' => $req]);
        $req->validate([
            'amount' => 'required|integer|min:1',
            'currency' => 'nullable|string' // default 'inr' or as desired
        ]);

        $sessionToken = $req->header('X-App-Session-Token');\Log::error('sessionToken', ['sessionToken' => $sessionToken]);
        if (!$sessionToken) return response()->json(['error'=>'missing_session'], 401);

        $ms = MobileSession::where('session_token', $sessionToken)
            ->where('expires_at', '>', now())
            ->first();

        if (!$ms) return response()->json(['error'=>'invalid_session'], 401);

        $account = $ms->account;
        if (!$account) return response()->json(['error'=>'no_connected_account'], 404);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $amount = $req->input('amount');
        $currency = $req->input('currency', 'usd'); // change as necessary

        try {
            // create PaymentIntent for terminal (card_present)
            $pi = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_types' => ['card_present'],
                'capture_method' => 'manual',//'automatic',
            ], [
                'stripe_account' => $account->stripe_user_id
            ]);

            // return the payment intent id & client_secret for the client (Terminal SDK needs the PaymentIntent)
            return response()->json([
                'id' => $pi->id,
                'client_secret' => $pi->client_secret,
            ]);
        } catch (\Exception $e) {
            \Log::error('CreatePaymentIntent error: '.$e->getMessage());
            return response()->json(['error'=>'create_payment_intent_failed', 'message'=>$e->getMessage()], 500);
        }
    }

    // Optional: retrieve payment intent if needed later
    public function retrievePaymentIntent(Request $req, $piId)
    {
        $sessionToken = $req->header('X-App-Session-Token');
        if (!$sessionToken) return response()->json(['error'=>'missing_session'], 401);

        $ms = MobileSession::where('session_token', $sessionToken)->first();
        if (!$ms) return response()->json(['error'=>'invalid_session'], 401);

        $account = $ms->account;
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $pi = PaymentIntent::retrieve($piId, ['stripe_account' => $account->stripe_user_id]);
            return response()->json($pi);
        } catch (\Exception $e) {
            return response()->json(['error'=>'retrieve_failed', 'message'=>$e->getMessage()], 500);
        }
    }
}
