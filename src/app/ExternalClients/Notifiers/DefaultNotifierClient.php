<?php

namespace App\ExternalClients\Notifiers;

use App\Models\TransactionFrom;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DefaultNotifierClient implements NotifierInterface
{
    /**
     * @param TransactionFrom $transaction
     * @return Response
     * @throws Throwable
     */
    public function notify(TransactionFrom $transaction): Response
    {
        try {
            return Http::get(env('DEFAULT_NOTIFIER_URL'));
        } catch (Throwable $e) {
            Log::error("Error trying authorize transaction " . $transaction->getId(), [$e->getTraceAsString()]);

            throw $e;
        }
    }
}
