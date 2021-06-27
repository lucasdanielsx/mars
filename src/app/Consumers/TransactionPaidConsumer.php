<?php

namespace App\Consumers;

use App\Helpers\Enums\Queue;
use App\Helpers\Enums\TransactionStatus;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Models\TransactionFrom;
use Aws\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionPaidConsumer extends Consumer
{
    /**
     * @param SqsHelper $sqsHelper
     * @param $transactionFrom
     * @param Result $messages
     * @param $index
     */
    private function notifyQueue(SqsHelper $sqsHelper, $transactionFrom, Result $messages, $index): void
    {
        $this->sendMessage(Queue::NOTIFY_CLIENT, $sqsHelper, $transactionFrom->toArray());
        $this->deleteMessage(Queue::TRANSACTION_PAID, $sqsHelper, $messages, $index,);
    }

    /**
     * @param TransactionFrom $transactionFrom
     */
    private function updateAll(TransactionFrom $transactionFrom): void
    {
        DB::transaction(function () use ($transactionFrom) {
            $transactionFrom->update();
            $transactionFrom->transaction->update();
            $transactionFrom->transaction->wallet->update();
        });
    }

    public function process()
    {
        Log::info("Starting " . self::class . " process");

        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $messages = $this->getMessages(Queue::TRANSACTION_PAID, $sqsHelper);

        foreach ($messages->get('Messages') as $index => $message) {
            try {
                $transactionFrom = $this->validAndGetBodyMessage($message['Body']);

                if ($transactionFrom->status == TransactionStatus::PAID) {
                    Log::error("Transaction " . $transactionFrom->id . " is already processed");

                    $this->notifyQueue($sqsHelper, $transactionFrom, $messages, $index);

                    continue;
                }

                $transactionFrom->status = TransactionStatus::PAID;
                $transactionFrom->transaction->status = TransactionStatus::PAID;
                $transactionFrom->transaction->wallet->amount += $transactionFrom->transaction->amount;

                $this->updateAll($transactionFrom);

                $this->notifyQueue($sqsHelper, $transactionFrom, $messages, $index);

                Log::info("Transaction " . $transactionFrom->id . " was processed");
            } catch (Throwable $e) {
                Log::error("Error trying process transaction: " . $e->getMessage(), [$e->getTraceAsString()]);

                continue;
            }
        }

        Log::info("Finished " . self::class . " process");
    }
}
