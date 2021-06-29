<?php

namespace App\Consumers;

use App\Helpers\Enums\EventType;
use App\Helpers\Enums\Queue;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\ExternalClients\Authorizers\DefaultAuthorizerClient;
use App\Models\Event;
use App\Models\TransactionFrom;
use Aws\Result;
use Illuminate\Http\Client\Response;
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

    public function process()
    {
        Log::info("Starting " . self::class . " process");

        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $messages = $this->getMessages(Queue::MARS_AUTHORIZE_TRANSACTION, $sqsHelper);

        if (!empty($messages->get('Messages'))) {
            foreach ($messages->get('Messages') as $index => $message) {
                $transactionFrom = $this->validAndGetBodyMessage($message);
                $messageId = $message['MessageId'];

                try {
                    $events = json_decode($transactionFrom->events);

                    if (!empty($events) && !empty(array_filter($events, function ($var) {
                            return $var->type == EventType::AUTHORIZED;
                        }))) {
                        $this->transactionIsAlreadyProcessed(Queue::MARS_TRANSACTION_PAID, $transactionFrom, $sqsHelper, $messages, $index);
                        continue;
                    }

                    if (!empty($events) && !empty(array_filter($events, function ($var) {
                            return $var->type == EventType::NOT_AUTHORIZED;
                        }))) {
                        $this->transactionIsAlreadyProcessed(Queue::MARS_TRANSACTION_NOT_PAID, $transactionFrom, $sqsHelper, $messages, $index);
                        continue;
                    }

                    $client = new DefaultAuthorizerClient();
                    $response = $client->authorize($transactionFrom);

                    if ($response->status() == 200) {
                        $this->authorizedFlow($transactionFrom, $response, $messageId, $sqsHelper, $messages, $index);
                        continue;
                    }

                    $this->notAuthorizedFlow($transactionFrom, $response, $messageId, $sqsHelper, $messages, $index);

                    Log::info("Transaction " . $transactionFrom->id . " was not authorized");
                } catch (Throwable $e) {
                    Log::error("Error trying process transaction " . $transactionFrom->id . ". " . $e->getTraceAsString());

                    $this->errorFlow($transactionFrom, ["error" => $e->getMessage()], $messageId, $sqsHelper, $messages, $index);

                    continue;
                }
            }
        }

        Log::info("Finished " . self::class . " process");
    }

    /**
     * @param string $queueToSend
     * @param TransactionFrom $transactionFrom
     * @param SqsHelper $sqsHelper
     * @param Result $messages
     * @param $index
     * @throws Throwable
     */
    private function transactionIsAlreadyProcessed(string $queueToSend, TransactionFrom $transactionFrom, SqsHelper $sqsHelper, Result $messages, $index): void
    {
        Log::error("Transaction " . $transactionFrom->id . " is already processed");

        $this->notifyQueueAndRemoveMessage($queueToSend, Queue::MARS_AUTHORIZE_TRANSACTION, $sqsHelper, $transactionFrom, $messages, $index);
    }

    private function convertEvent(string $transactionId, array $payload, string $messageId, string $type): Event
    {
        $event = new Event();
        $event->id = Uuid::uuid4();
        $event->fkTransactionFromId = $transactionId;
        $event->payload = json_encode($payload);
        $event->messageId = $messageId;
        $event->type = $type;

        return $event;
    }

    private function saveAll($eventAuthorization, $event): void
    {
        DB::transaction(function () use ($eventAuthorization, $event) {
            $eventAuthorization->save();
            $event->save();
        });
    }

    /**
     * @throws Throwable
     */
    private function errorFlow(TransactionFrom $transactionFrom, array $message, $messageId, SqsHelper $sqsHelper, Result $messages, $index): Event
    {
        $eventAuthorization = $this->convertEvent($transactionFrom->id, $message, $messageId, EventType::ERROR);
        $event = $this->convertEvent($transactionFrom->id, [], $messageId, EventType::NOT_PAID);

        $this->saveAll($eventAuthorization, $event);
        $this->notifyQueueAndRemoveMessage(Queue::MARS_TRANSACTION_NOT_PAID, Queue::MARS_AUTHORIZE_TRANSACTION, $sqsHelper, $transactionFrom, $messages, $index);

        Log::info("Transaction " . $transactionFrom->id . " was not authorized");
    }

    /**
     * @throws Throwable
     */
    private function authorizedFlow(TransactionFrom $transactionFrom, Response $response, $messageId, SqsHelper $sqsHelper, Result $messages, $index): void
    {
        $eventAuthorization = $this->convertEvent($transactionFrom->id, json_decode($response->body(), true), $messageId, EventType::AUTHORIZED);
        $event = $this->convertEvent($transactionFrom->id, [], $messageId, EventType::PAID);

        $this->saveAll($eventAuthorization, $event);
        $this->notifyQueueAndRemoveMessage(Queue::MARS_TRANSACTION_PAID, Queue::MARS_AUTHORIZE_TRANSACTION, $sqsHelper, $transactionFrom, $messages, $index);

        Log::info("Transaction " . $transactionFrom->id . " was authorized");
    }

    /**
     * @throws Throwable
     */
    private function notAuthorizedFlow(TransactionFrom $transactionFrom, Response $response, $messageId, SqsHelper $sqsHelper, Result $messages, $index): void
    {
        $eventNotAuthorization = $this->convertEvent($transactionFrom->id, json_decode($response->body(), true), $messageId, EventType::NOT_AUTHORIZED);
        $event = $this->convertEvent($transactionFrom->id, [], $messageId, EventType::PAID);

        $this->saveAll($eventNotAuthorization, $event);
        $this->notifyQueueAndRemoveMessage(Queue::MARS_TRANSACTION_NOT_PAID, Queue::MARS_AUTHORIZE_TRANSACTION, $sqsHelper, $transactionFrom, $messages, $index);
    }
}
