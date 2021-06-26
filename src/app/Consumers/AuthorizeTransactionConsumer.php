<?php

namespace App\Consumers;

use App\Helpers\Enums\Queues;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Http\Clients\Authorizers\DefaultAuthorizerClient;
use App\Models\Event;
use App\Models\TransactionFrom;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Throwable;

class AuthorizeTransactionConsumer extends Consumer
{
    /**
     * @param TransactionFrom $transaction
     * @param array $message
     * @param int $statusCode
     * @param string $messageId
     * @return array
     */
    private function convertEvent(TransactionFrom $transaction, array $message, int $statusCode, string $messageId)
    {
        list($type, $queue) = ($statusCode == 200) ? ['transaction_authorized', 'transaction_paid'] : ['transaction_not_authorized', 'transaction_not_paid'];

        $event = new Event();
        $event->setId(Uuid::uuid4());
        $event->setFkTransactionFromId($transaction->getId());
        $event->setType($type);
        $event->setPayload($message);
        $event->setMessageId($messageId);

        return [$event, $queue];
    }

    public function process()
    {
        Log::info("Starting " . self::class . " process");

        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $messages = $sqsHelper->getMessages(Queues::AUTHORIZE_TRANSACTION);

        foreach ($messages->get('Messages') as $index => $message) {
            try {
                $transaction = new TransactionFrom(json_decode($message['Body'], true));

                $client = new DefaultAuthorizerClient();
                $response = $client->authorize($transaction);

                list($event, $queue) = $this->convertEvent($transaction, json_decode($response->body(), true), $response->status(), $message['MessageId']);

                $event->save();

                $sqsHelper->sendMessage($queue, $transaction->toArray());
                $sqsHelper->deleteMessage(Queues::AUTHORIZE_TRANSACTION, $messages, $index);

                Log::info("TransactionFrom " . $transaction->getId() . " was authorized");
            } catch (Throwable $e) {
                Log::error("Error trying process transaction", [$e->getTraceAsString()]);

                continue;
            }
        }
    }
}
