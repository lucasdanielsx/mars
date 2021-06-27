<?php

namespace App\Consumers;

use App\Helpers\Enums\EventType;
use App\Helpers\Enums\Queue;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Http\Clients\Authorizers\DefaultAuthorizerClient;
use App\Models\Event;
use App\Models\TransactionFrom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Throwable;

class AuthorizeTransactionConsumer extends Consumer
{
    /**
     * @param $eventAuthorization
     * @param $event
     */
    private function saveAll($eventAuthorization, $event): void
    {
        DB::transaction(function () use ($eventAuthorization, $event) {
            $eventAuthorization->save();
            $event->save();
        });
    }

    /**
     * @param TransactionFrom $transaction
     * @param array $payload
     * @param int $statusCode
     * @param string $messageId
     * @return array
     */
    private function convertEvents(TransactionFrom $transaction, array $payload, int $statusCode, string $messageId)
    {
        $eventAuthorization = new Event();
        $eventAuthorization->id = Uuid::uuid4();
        $eventAuthorization->fkTransactionFromId = $transaction->id;
        $eventAuthorization->payload = json_encode($payload);
        $eventAuthorization->messageId = $messageId;
        $eventAuthorization->type = EventType::TRANSACTION_AUTHORIZED;

        $event = new Event();
        $event->id = Uuid::uuid4();
        $event->fkTransactionFromId = $transaction->id;
        $event->payload = json_encode([]);
        $event->messageId = $messageId;
        $event->type = EventType::TRANSACTION_PAID;

        if ($statusCode == 200) {
            return [$eventAuthorization, $event, Queue::TRANSACTION_PAID];
        }

        $eventAuthorization->type = EventType::TRANSACTION_NOT_AUTHORIZED;
        $event->type = EventType::TRANSACTION_NOT_PAID;

        return [$eventAuthorization, $event, Queue::TRANSACTION_NOT_PAID];
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

                list($eventAuthorization, $event, $queue) = $this->convertEvents($transaction, json_decode($response->body(), true), $response->status(), $message['MessageId']);

                $this->saveAll($eventAuthorization, $event);

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
