<?php

namespace App\Consumers;

use App\Helpers\Enums\Queue;
use App\Helpers\Enums\TransactionStatus;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Models\TransactionFrom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionNotPaidConsumer extends Consumer
{
    public function __invoke()
    {
        $this->process();
    }

    public function process()
    {
        Log::info("Starting " . self::class . " process");

        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $messages = $this->getMessages(Queue::MARS_TRANSACTION_NOT_PAID, $sqsHelper);

        if (!empty($messages->get('Messages'))) {
            foreach ($messages->get('Messages') as $index => $message) {
                try {
                    $transactionFrom = $this->validAndGetBodyMessage($message['Body']);

                    if ($transactionFrom->status == TransactionStatus::NOT_PAID) {
                        Log::error("Transaction . " . $transactionFrom->id . " is already processed");
                        $this->notifyQueueAndRemoveMessage(Queue::MARS_NOTIFY_CLIENT, Queue::MARS_TRANSACTION_NOT_PAID, $sqsHelper, $transactionFrom, $messages, $index);

                        continue;
                    }

                    $transactionFrom->status = TransactionStatus::NOT_PAID;
                    $transactionFrom->transaction->status = TransactionStatus::NOT_PAID;
                    $transactionFrom->wallet->amount += $transactionFrom->transaction->amount;

                    $this->updateAll($transactionFrom);

                    $this->notifyQueueAndRemoveMessage(Queue::MARS_NOTIFY_CLIENT, Queue::MARS_TRANSACTION_NOT_PAID, $sqsHelper, $transactionFrom, $messages, $index);

                    Log::info("Transaction " . $transactionFrom->id . " was processed");
                } catch (Throwable $e) {
                    Log::error("Error trying process transaction: " . $e->getMessage(), [$e->getTraceAsString()]);

                    continue;
                }
            }
        }

        Log::info("Finished " . self::class . " process");
    }

    private function updateAll(TransactionFrom $transactionFrom): void
    {
        DB::transaction(function () use ($transactionFrom) {
            $transactionFrom->update();
            $transactionFrom->transaction->update();
            $transactionFrom->transaction->wallet->update();
            $transactionFrom->wallet->update();
        });
    }
}
