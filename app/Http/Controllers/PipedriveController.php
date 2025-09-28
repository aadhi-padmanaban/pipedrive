<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\PipedriveToken;
use Carbon\Carbon;

use App\Services\PipedriveService;
use App\Services\StripeService;
use Illuminate\Support\Facades\Log;


class PipedriveController extends Controller
{
    protected $pipedriveService;
    protected $stripeService;

    public function __construct(PipedriveService $pipedriveService, StripeService $stripeService)
    {
        $this->pipedriveService = $pipedriveService;
        $this->stripeService = $stripeService;
    }

    public function oauthCallback(Request $request)
    {
        try {
            $code = $request->query('code');
            $tokens = $this->pipedriveService->exchangeCodeForTokens($code);

            Log::info('OAuth token response:', $tokens);

            $userInfo = $this->pipedriveService->getUserInfo($tokens['access_token'], $tokens['api_domain']);
            $companyId = $userInfo['data']['company_id'] ?? null;

            $this->pipedriveService->saveTokens($tokens, $companyId);

            return redirect($tokens['api_domain']);
        } catch (\Exception $e) {
            Log::error('OAuth callback error: ' . $e->getMessage());
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
        Log::info('transactions input:', $request->all());

        $companyId = $request->query('companyId');
        $personId = $request->query('personId');

        $email = $this->pipedriveService->getContactEmail($companyId, $personId);
        Log::info('email:', ['email' => $email]);

        if (!$email) {
            return response()->json(['error' => 'Email required'], 400);
        }

        $data = $this->stripeService->getTransactionsByEmail($email);

        if (!$data) {
            return response()->json(['error' => 'Failed to fetch Stripe data'], 500);
        }

        $invoices = [];
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

        $charges = [];
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

        return response()->json([
            'transactions' => [
                'invoices' => $invoices,
                'charges' => $charges,
            ]
        ]);
    }
}


