<?php

namespace App\Consumers;

use App\Helpers\Enums\EventType;
use App\Helpers\Enums\Queue;
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
     * @param array $payload
     * @param int $statusCode
     * @param string $messageId
     * @return array
     */
    private function convertEvent(TransactionFrom $transaction, array $payload, int $statusCode, string $messageId)
    {
        list($type, $queue) = ($statusCode == 200) ? [EventType::TRANSACTION_AUTHORIZED, Queue::TRANSACTION_PAID] : [EventType::TRANSACTION_AUTHORIZED, Queue::TRANSACTION_NOT_PAID];

        $event = new Event();
        $event->id = Uuid::uuid4();
        $event->fkTransactionFromId = $transaction->id;
        $event->type = $type;
        $event->payload = json_encode($payload);
        $event->messageId = $messageId;

        return [$event, $queue];
    }

    public function process()
    {
        Log::info("Starting " . self::class . " process");

        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $messages = $sqsHelper->getMessages(Queue::AUTHORIZE_TRANSACTION);

        foreach ($messages->get('Messages') as $index => $message) {
            try {
                $transaction = new TransactionFrom(json_decode($message['Body'], true));

                $client = new DefaultAuthorizerClient();
                $response = $client->authorize($transaction);

                list($event, $queue) = $this->convertEvent($transaction, json_decode($response->body(), true), $response->status(), $message['MessageId']);

                $event->save();

                $sqsHelper->sendMessage($queue, $transaction->toArray());
                $sqsHelper->deleteMessage(Queue::AUTHORIZE_TRANSACTION, $messages, $index);

                Log::info("Transaction " . $transaction->id . " was authorized");
            } catch (Throwable $e) {
                Log::error("Error trying process transaction " . $e->getMessage(), [$e->getTraceAsString()]);

                continue;
            }
        }
    }
}
