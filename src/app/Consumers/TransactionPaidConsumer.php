<?php

namespace App\Consumers;

use App\Helpers\Enums\EventType;
use App\Helpers\Enums\Queue;
use App\Helpers\Enums\TransactionStatus;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Models\TransactionFrom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionPaidConsumer extends Consumer
{
    /**
     * @param $transactionFrom
     */
    private function updateAll($transactionFrom): void
    {
        DB::transaction(function () use ($transactionFrom) {
            $transactionFrom->update();
            $transactionFrom->transactionTo->update();
            $transactionFrom->transactionTo->wallet->update();
        });
    }

    public function process()
    {
        Log::info("Starting " . self::class . " process");

        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $messages = $sqsHelper->getMessages(Queue::TRANSACTION_PAID);

        foreach ($messages->get('Messages') as $index => $message) {
            try {
                $body = new TransactionFrom(json_decode($message['Body'], true));

                $transactionFrom = TransactionFrom::where('id', $body->id)->first();
                $transactionFrom->status = TransactionStatus::PAID;
                var_dump($transactionFrom->transactionTo->status);
                $transactionFrom->transactionTo->status = TransactionStatus::PAID;
                $transactionFrom->transactionTo->wallet->amount += $transactionFrom->transactionTo->amount;

                $this->updateAll($transactionFrom);

                $sqsHelper->sendMessage(Queue::NOTIFY_CLIENT, $transactionFrom->toArray());
                $sqsHelper->deleteMessage(EventType::TRANSACTION_PAID, $messages, $index);

                Log::info("TransactionFrom " . $transactionFrom->id . " was authorized");
            } catch (\Throwable $e) {
                Log::error("Error trying process transaction" . $e->getMessage(), [$e->getTraceAsString()]);

                continue;
            }
        }
    }
}
