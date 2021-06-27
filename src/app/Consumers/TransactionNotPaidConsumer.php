<?php

namespace App\Consumers;

use App\Helpers\Enums\TransactionStatus;
use App\Helpers\SqsHelper;
use App\Helpers\SqsUsEast1Client;
use App\Models\Event;
use App\Models\TransactionFrom;
use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class TransactionNotPaidConsumer extends Consumer
{
    /**
     * @param TransactionFrom $transaction
     * @return array
     */
    private function authorize(TransactionFrom $transaction): array
    {
        try {
            $response = Http::get(env('AUTHORIZER_URL'));

            list($event, $queue) = $this->mountEvent($transaction, json_decode($response->body(), true), $response->status());
        } catch (\Throwable $e) {
            Log::error("Error trying authorize transaction " . $transaction->id, [$e->getTraceAsString()]);

            list($event, $queue) = $this->mountEvent($transaction, ["error" => $e->getMessage()], 500);
        }

        return [$event, $queue];
    }

    /**
     * @param TransactionFrom $transaction
     * @param array $message
     * @param int $statusCode
     * @return array
     */
    private function mountEvent(TransactionFrom $transaction, array $message, int $statusCode)
    {
        list($type, $queue) = ($statusCode == 200) ? ['transaction_authorized', 'transaction_paid'] : ['transaction_not_authorized', 'transaction_not_paid'];

        $event = new Event();
        $event->id = Uuid::uuid4();
        $event->fk_transaction_id = $transaction->id;
        $event->type = $type;
        $event->payload = json_encode($message, true);

        return [$event, $queue];
    }

    public function process()
    {
        Log::info("Starting " . self::class . " process");

        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $messages = $sqsHelper->getMessages('mars-transaction_not_paid');

        foreach ($messages->get('Messages') as $index => $message) {
            try {
                $transaction = new TransactionFrom(json_decode($message['Body'], true));
                $event = null;

                list($event, $queue) = $this->authorize($transaction);
                $event->save();

                $sqsHelper->sendMessage($queue, $transaction->toArray());
                $sqsHelper->deleteMessage(TransactionStatus::AUTHORIZE_TRANSACTION, $messages, $index);

                Log::info("TransactionFrom " . $transaction->id . " was authorized");
            } catch (\Throwable $e) {
                Log::error("Error trying process transaction", [$e->getTraceAsString()]);

                continue;
            }
        }
    }
}
