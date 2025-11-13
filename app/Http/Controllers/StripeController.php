<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Stripe\Stripe;
use Stripe\Charge;

use App\Models\MobileSession;
use App\Models\ConnectedAccount;

class StripeController extends Controller
{
    public function payments(Request $req)
    {
        
        $token = $req->header('X-App-Session-Token');

        if (!$token) {
            return response()->json(['error' => 'Missing session token'], 422);
        }

        $mobile = MobileSession::where('session_token', $token)->first();
        if (!$mobile) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        $account = ConnectedAccount::find($mobile->connected_account_id);
        if (!$account) {
            return response()->json(['error' => 'No connected account'], 404);
        }

        // Fetch payments for connected account
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $charges = Charge::all([
            'limit' => 25
        ], [
            'stripe_account' => $account->stripe_user_id
        ]);

        $list = collect($charges->data)->map(function ($c) {
            return [
                'id' => $c->id,
                'amount' => $c->amount / 100,
                'currency' => $c->currency,
                'status' => $c->status,
                'created' => date('Y-m-d H:i:s', $c->created),
                'card_last4' => $c->payment_method_details->card->last4 ?? null,
                'card_brand' => $c->payment_method_details->card->brand ?? null,
            ];
        });

        return response()->json($list);
    }

}
