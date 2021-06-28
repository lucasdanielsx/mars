<?php

namespace App\Consumers;

use App\Helpers\Enums\EventType;
use App\Helpers\Enums\Queue;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\ExternalClients\Authorizers\DefaultAuthorizerClient;
use App\Models\Event;
use App\Models\TransactionFrom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Throwable;

class AuthorizeTransactionConsumer extends Consumer
{
    public function __invoke()
    {
        $this->process();
    }

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

        $event = new Event();
        $event->id = Uuid::uuid4();
        $event->fkTransactionFromId = $transaction->id;
        $event->payload = json_encode([]);
        $event->messageId = $messageId;

        if ($statusCode == 200) {
            $eventAuthorization->type = EventType::TRANSACTION_AUTHORIZED;
            $event->type = EventType::TRANSACTION_PAID;

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
        $messages = $this->getMessages(Queue::AUTHORIZE_TRANSACTION, $sqsHelper);

        if (!empty($messages->get('Messages'))) {
            foreach ($messages->get('Messages') as $index => $message) {
                try {
                    $transactionFrom = $this->validAndGetBodyMessage($message['Body']);
echo $transactionFrom->events;
                    $types = array_map('type', $transactionFrom->events);

                    if (in_array(EventType::TRANSACTION_AUTHORIZED, $types)) {
                        Log::error("Transaction . " . $transactionFrom->id . " is already processed");

                        $this->notifyQueueAndRemoveMessage(Queue::TRANSACTION_PAID, Queue::AUTHORIZE_TRANSACTION, $sqsHelper, $transactionFrom, $messages, $index);

                        continue;
                    }

                    if (in_array(EventType::TRANSACTION_NOT_AUTHORIZED, $types)) {
                        Log::error("Transaction . " . $transactionFrom->id . " is already processed");

                        $this->notifyQueueAndRemoveMessage(Queue::TRANSACTION_NOT_PAID, Queue::AUTHORIZE_TRANSACTION, $sqsHelper, $transactionFrom, $messages, $index);

                        continue;
                    }

                    $client = new DefaultAuthorizerClient();
                    $response = $client->authorize($transactionFrom);

                    list($eventAuthorization, $event, $queue) = $this->convertEvents($transactionFrom, json_decode($response->body(), true), $response->status(), $message['MessageId']);

                    $this->saveAll($eventAuthorization, $event);

                    $this->notifyQueueAndRemoveMessage($queue, Queue::AUTHORIZE_TRANSACTION, $sqsHelper, $transactionFrom, $messages, $index);

                    Log::info("Transaction " . $transactionFrom->id . " was authorized");
                } catch (Throwable $e) {
                    Log::error("Error trying process transaction " . $e->getMessage(), [$e->getTraceAsString()]);

                    continue;
                }
            }
        }

        Log::info("Finished " . self::class . " process");
    }
}
