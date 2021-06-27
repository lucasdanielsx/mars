<?php

namespace App\Helpers\Enums;

abstract class EventType extends EnumBase
{
    const TRANSACTION_AUTHORIZED = 'transaction_authorized';
    const TRANSACTION_NOT_AUTHORIZED = 'transaction_not_authorized';
}
