<?php

namespace App\Consumers;

use App\Helpers\Sqs\SqsHelper;
use App\Models\TransactionFrom;
use Aws\Result;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

abstract class Consumer
{
    abstract function process();

    /**
     * @param $body
     * @return TransactionFrom
     */
    protected function validAndGetBodyMessage($body): TransactionFrom
    {
        $body = new TransactionFrom(json_decode($body, true));

        $transactionFrom = TransactionFrom::where('id', $body->id)->first();

        if (empty($transactionFrom)) {
            throw new RuntimeException("Transaction . " . $body->id . " not found");
        }

        return $transactionFrom;
    }

    /**
     * @param string $queueToSend
     * @param string $queueToDelete
     * @param SqsHelper $sqsHelper
     * @param TransactionFrom $transactionFrom
     * @param Result $messages
     * @param $index
     * @throws Throwable
     */
    protected function notifyQueueAndRemoveMessage(string $queueToSend, string $queueToDelete, SqsHelper $sqsHelper, TransactionFrom $transactionFrom, Result $messages, $index): void
    {
        try {
            $this->sendMessage($queueToSend, $sqsHelper, $transactionFrom);
            $this->deleteMessage($queueToDelete, $sqsHelper, $messages, $index);
        } catch (Throwable $e) {
            Log::error("Error during notify queue " . $queueToSend . " and remove message from queue " . $queueToDelete . ". " . $e->getTraceAsString());

            throw $e;
        }
    }

    /**
     * @param String $queue
     * @param SqsHelper $sqsHelper
     * @return Result
     * @throws Throwable
     */
    protected function getMessages(string $queue, SqsHelper $sqsHelper): Result
    {
        try {
            return $sqsHelper->getMessages($queue);
        } catch (Throwable $e) {
            Log::error("Error trying get messages from queue  " . $queue . ". " . $e->getTraceAsString());

            throw $e;
        }
    }

    /**
     * @param String $queue
     * @param SqsHelper $sqsHelper
     * @param Result $messages
     * @param int $index
     * @throws Throwable
     */
    protected function deleteMessage(string $queue, SqsHelper $sqsHelper, Result $messages, int $index): void
    {
        try {
            $sqsHelper->deleteMessage($queue, $messages, $index);
        } catch (Throwable $e) {
            Log::error("Error delete message from queue  " . $queue . ". " . $e->getTraceAsString());

            throw $e;
        }
    }

    /**
     * @param String $queue
     * @param SqsHelper $sqsHelper
     * @param TransactionFrom $transactionFrom
     * @throws Throwable
     */
    protected function sendMessage(string $queue, SqsHelper $sqsHelper, TransactionFrom $transactionFrom): void
    {
        try {
            $sqsHelper->sendMessage($queue, $transactionFrom->toArray());
        } catch (Throwable $e) {
            Log::error("Error trying send message to queue  " . $queue . ". " . $e->getTraceAsString());

            throw $e;
        }
    }
}
