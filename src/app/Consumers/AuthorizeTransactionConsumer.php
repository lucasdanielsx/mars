<?php

namespace App\Consumers;

use App\Helpers\Enums\Queues;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class AuthorizeTransactionConsumer extends Consumer
{
    /**
     * @param Transaction $transaction
     * @return array
     */
    private function authorize(Transaction $transaction, string $messageId): array
    {
        try {
            $response = Http::get(env('AUTHORIZER_URL'));

            list($event, $queue) = $this->convertEvent($transaction, json_decode($response->body(), true), $response->status(), $messageId);
        } catch (\Throwable $e) {
            Log::error("Error trying authorize transaction " . $transaction->getId(), [$e->getTraceAsString()]);

            list($event, $queue) = $this->convertEvent($transaction, ["error" => $e->getMessage()], 500, $messageId);
        }

        return [$event, $queue];
    }

    /**
     * @param Transaction $transaction
     * @param array $message
     * @param int $statusCode
     * @return array
     */
    private function convertEvent(Transaction $transaction, array $message, int $statusCode, string $messageId)
    {
        list($type, $queue) = ($statusCode == 200) ? ['transaction_authorized', 'transaction_paid'] : ['transaction_not_authorized', 'transaction_not_paid'];

        $event = new Event();
        $event->setId(Uuid::uuid4());
        $event->setFkTransactionId($transaction->getId());
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
                $transaction = new Transaction(json_decode($message['Body'], true));

                list($event, $queue) = $this->authorize($transaction, $message['MessageId']);

                $event->save();

                $sqsHelper->sendMessage($queue, $transaction->toArray());
                $sqsHelper->deleteMessage(Queues::AUTHORIZE_TRANSACTION, $messages, $index);

                Log::info("Transaction " . $transaction->getId() . " was authorized");
            } catch (\Throwable $e) {
                Log::error("Error trying process transaction", [$e->getTraceAsString()]);

                continue;
            }
        }
    }
}
