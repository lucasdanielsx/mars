<?php

namespace App\ExternalClients\Authorizers;

use App\Models\TransactionFrom;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DefaultAuthorizerClient implements AuthorizerInterface
{
    /**
     * @param TransactionFrom $transaction
     * @return Response
     * @throws Throwable
     */
    public function authorize(TransactionFrom $transaction): Response
    {
        try {
            return Http::get(env('DEFAULT_AUTHORIZER_URL'));
        } catch (Throwable $e) {
            Log::error("Error trying authorize transaction " . $transaction->getId(), [$e->getTraceAsString()]);

            throw $e;
        }
    }
}
