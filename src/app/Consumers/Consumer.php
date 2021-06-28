<?php

namespace App\Consumers;

use App\Helpers\Sqs\SqsHelper;
use App\Models\TransactionFrom;
use Aws\Result;
use RuntimeException;

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

        $transactionFrom = TransactionFrom::find($body->id)->first();

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
     */
    protected function notifyQueueAndRemoveMessage(string $queueToSend, string $queueToDelete, SqsHelper $sqsHelper, TransactionFrom $transactionFrom, Result $messages, $index): void
    {
        $this->sendMessage($queueToSend, $sqsHelper, $transactionFrom->toArray());
        $this->deleteMessage($queueToDelete, $sqsHelper, $messages, $index);
    }

    /**
     * @param String $queue
     * @param SqsHelper $sqsHelper
     * @return Result
     */
    protected function getMessages(string $queue, SqsHelper $sqsHelper): Result
    {
        return $sqsHelper->getMessages($queue);
    }

    /**
     * @param String $queue
     * @param SqsHelper $sqsHelper
     * @param Result $messages
     * @param int $index
     */
    protected function deleteMessage(string $queue, SqsHelper $sqsHelper, Result $messages, int $index): void
    {
        $sqsHelper->deleteMessage($queue, $messages, $index);
    }

    /**
     * @param String $queue
     * @param SqsHelper $sqsHelper
     * @param TransactionFrom $transactionFrom
     */
    protected function sendMessage(string $queue, SqsHelper $sqsHelper, TransactionFrom $transactionFrom): void
    {
        $sqsHelper->sendMessage($queue, $transactionFrom->toArray());
    }
}
