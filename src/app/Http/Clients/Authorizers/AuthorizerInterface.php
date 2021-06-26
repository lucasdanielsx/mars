<?php

namespace App\Http\Clients\Authorizers;

use App\Models\TransactionFrom;
use Illuminate\Http\Client\Response;

interface AuthorizerInterface
{
    /**
     * @param TransactionFrom $transaction
     * @return Response
     */
    function authorize(TransactionFrom $transaction): Response;
}
