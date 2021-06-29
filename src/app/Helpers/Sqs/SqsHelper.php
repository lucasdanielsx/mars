<?php

namespace App\Helpers\Sqs;

use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class SqsHelper
{
    private $client;

    public function __construct(SqsClientInterface $client)
    {
        $this->client = $client::client();
    }

    public function getMessages(string $queue): \Aws\Result
    {
        try {
            return $this->client->receiveMessage(array(
                'AttributeNames' => ['SentTimestamp'],
                'MaxNumberOfMessages' => 10,
                'MessageAttributeNames' => ['All'],
                'QueueUrl' => env('SQS_PREFIX') . '/' . $queue,
                'WaitTimeSeconds' => 0
            ));
        } catch (AwsException $e) {
            Log::error("Error trying get messages from queue" . $queue, $e->toArray());

            throw $e;
        }
    }

    public function deleteMessage(string $queue, $result, int $index): \Aws\Result
    {
        try {
            return $this->client->deleteMessage([
                'QueueUrl' => env('SQS_PREFIX') . '/' . $queue,
                'ReceiptHandle' => $result->get('Messages')[$index]['ReceiptHandle']
            ]);
        } catch (AwsException $e) {
            Log::error("Error trying delete message from queue " . $queue . ". " .$e->getTraceAsString());

            throw $e;
        }
    }

    public function sendMessage(string $queue, array $message): \Aws\Result
    {
        try {
            $params = [
                'DelaySeconds' => 0,
                'MessageAttributes' => [],
                'MessageBody' => json_encode($message),
                'QueueUrl' => env('SQS_PREFIX') . '/' . $queue
            ];

            return $this->client->sendMessage($params);
        } catch (AwsException $e) {
            Log::error("Error trying send messages to queue " . $queue, $e->toArray());

            throw $e;
        }
    }
}
