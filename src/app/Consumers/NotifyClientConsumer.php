<?php

namespace App\Consumers;

use App\ExternalClients\Notifiers\DefaultNotifierClient;
use App\Helpers\Enums\EventType;
use App\Helpers\Enums\Queue;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Models\Event;
use App\Models\TransactionFrom;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Throwable;

class NotifyClientConsumer extends Consumer
{
    public function __invoke()
    {
        $this->process();
    }

    /**
     * @param TransactionFrom $transaction
     * @param array $message
     * @param string $messageId
     * @return Event
     */
    private function convertEvent(TransactionFrom $transaction, array $message, string $messageId): Event
    {
        $event = new Event();
        $event->id = Uuid::uuid4();
        $event->fkTransactionFromId = $transaction->id;
        $event->type = EventType::TRANSACTION_NOTIFIED;
        $event->payload = json_encode($message, true);
        $event->messageId = $messageId;

        return $event;
    }

    public function process()
    {
        Log::info("Starting " . self::class . " process");

        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $messages = $this->getMessages(Queue::NOTIFY_CLIENT, $sqsHelper);

        if (!empty($messages->get('Messages'))) {
            foreach ($messages->get('Messages') as $index => $message) {
                try {
                    $transactionFrom = $this->validAndGetBodyMessage($message['Body']);

                    $types = array_map('type', $transactionFrom->events);

                    if (in_array(EventType::TRANSACTION_NOTIFIED, $types)) {
                        Log::error("Transaction . " . $transactionFrom->id . " is already processed");

                        $this->deleteMessage(Queue::NOTIFY_CLIENT, $sqsHelper, $messages, $index);

                        continue;
                    }

                    $client = new DefaultNotifierClient();
                    $response = $client->notify($transactionFrom);

                    if ($response->status() != 200) {
                        Log::error("Error trying notify transaction " . $transactionFrom->id);

                        continue;
                    }

                    $event = $this->convertEvent($transactionFrom, json_decode($response->body(), true), $message['MessageId']);

                    $event->save();

                    $this->deleteMessage(Queue::NOTIFY_CLIENT, $sqsHelper, $messages, $index);

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
