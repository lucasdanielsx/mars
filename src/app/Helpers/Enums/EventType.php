<?php

namespace App\Helpers\Enums;

abstract class EventType extends EnumBase
{
    const AUTHORIZED = 'authorized';
    const NOT_AUTHORIZED = 'not_authorized';
    const PAID = 'paid';
    const NOT_PAID = 'not_paid';
    const NOTIFIED = 'notified';
    const ERROR = 'error';
}
