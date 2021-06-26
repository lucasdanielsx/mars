<?php

namespace App\Helpers\Sqs;

use Aws\Sqs\SqsClient;

class SqsUsEast1Client implements SqsClientInterface
{
    public static function client(): SqsClient
    {
        return new SqsClient([
            'profile' => 'default',
            'region' => 'us-east-1',
            'version' => '2012-11-05'
        ]);
    }
}
