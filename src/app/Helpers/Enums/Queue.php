<?php

namespace App\Helpers\Enums;

abstract class Queue extends EnumBase
{
    const MARS_AUTHORIZE_TRANSACTION = 'mars-authorize_transaction';
    const MARS_NOTIFY_CLIENT = 'mars-notify_client';
    const MARS_TRANSACTION_NOT_PAID = 'mars-transaction_not_paid';
    const MARS_TRANSACTION_PAID = 'mars-transaction_paid';
}
