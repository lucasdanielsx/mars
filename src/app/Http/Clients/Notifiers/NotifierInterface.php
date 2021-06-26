<?php

namespace App\Http\Clients\Notifiers;

use App\Models\TransactionFrom;
use Illuminate\Http\Client\Response;

interface NotifierInterface
{
    /**
     * @param TransactionFrom $transaction
     * @return Response
     */
    function notify(TransactionFrom $transaction): Response;
}
