<?php

namespace App\Helpers\Sqs;

use Aws\Sqs\SqsClient;

interface SqsClientInterface
{
    static function client(): SqsClient;
}
