<?php

namespace App\Helpers\Enums;

abstract class Queues extends EnumBase
{
    const AUTHORIZE_TRANSACTION = 'mars-authorize_transaction';
    const NOTIFY_CLIENT = 'mars-notify_client';
    const TRANSACTION_NOT_PAID = 'mars-transaction_not_paid';
    const TRANSACTION_PAID = 'mars-transaction_paid';
}
