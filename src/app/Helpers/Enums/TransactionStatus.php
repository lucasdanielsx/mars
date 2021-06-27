<?php

namespace App\Helpers\Enums;

abstract class TransactionStatus extends EnumBase
{
    const CREATED = 'created';
    const NOT_PAID = 'not_paid';
    const PAID = 'paid';
}
