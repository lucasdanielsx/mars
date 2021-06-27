<?php

namespace App\Http\Controllers;

use App\Consumers\AuthorizeTransactionConsumer;
use App\Consumers\TransactionNotPaidConsumer;
use App\Consumers\TransactionPaidConsumer;
use App\Helpers\Enums\TransactionStatus;
use App\Helpers\Enums\UserType;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Models\TransactionFrom;
use App\Models\TransactionTo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Boolean;
use Ramsey\Uuid\Uuid;
use Throwable;

class TransactionController extends Controller
{
    /**
     * @param string $message
     * @param array $items
     * @param int $statusCode
     * @return Response
     */
    private function response(string $message, array $items, int $statusCode): Response
    {
        $response = new Response();
        $response->setContent(['message' => $message, 'items' => $items, 'status' => $statusCode]);
        $response->setStatusCode($statusCode);
        $response->header('Content-Type', 'application/json');

        return $response;
    }

    private function publish(string $queue, array $message): void
    {
        $sqsHelper = new SqsHelper(new SqsUsEast1Client());
        $sqsHelper->sendMessage($queue, $message);
    }

    /**
     * @param TransactionFrom $transactionFrom
     * @param TransactionTo $transactionTo
     * @param $userFrom
     */
    private function saveAll(TransactionFrom $transactionFrom, TransactionTo $transactionTo, $userFrom): void
    {
        DB::transaction(function () use ($transactionFrom, $transactionTo, $userFrom) {
            $transactionFrom->save();
            $transactionTo->save();
            $userFrom->wallet->update();
        });
    }

    /**
     * @param String $value
     * @return mixed
     */
    private function getUserByDocumentValue(string $value)
    {
        return User::where('document_value', '=', $value)->first();
    }

    /**
     * @param $userFrom
     * @param $userTo
     * @param int $amount
     * @return false|Response
     */
    private function userRules($userFrom, $userTo, int $amount)
    {
        if (empty($userFrom))
            return $this->response('Payer not exist', [], 400);

        if ($userFrom->type != UserType::CUSTOMER)
            return $this->response("Payer isn't allowed to make transactions", [], 403);

        if ($userFrom->wallet->amount < $amount)
            return $this->response("Insufficient funds", [], 400);

        if (empty($userTo))
            return $this->response('Payee not exist', [], 400);

        return false;
    }

    /**
     * @param User $userFrom
     * @param User $userTo
     * @param Request $request
     * @return array
     */
    private function convertTransaction(User $userFrom, User $userTo, Request $request): array
    {
        $transactionFromId = Uuid::uuid4();

        $transactionFrom = new TransactionFrom();
        $transactionFrom->id = $transactionFromId;
        $transactionFrom->fkWalletId = $userFrom->wallet->id;
        $transactionFrom->amount = $request['amount'];
        $transactionFrom->status = TransactionStatus::CREATED;
        $transactionFrom->payload = $request->getContent();

        $transactionTo = new TransactionTo();
        $transactionTo->id = Uuid::uuid4();
        $transactionTo->fkTransactionFromId = $transactionFromId;
        $transactionTo->fkWalletId = $userTo->wallet->id;
        $transactionTo->amount = $request['amount'];
        $transactionTo->status = TransactionStatus::CREATED;
        $transactionTo->payload = $request->getContent();

        return [$transactionFrom, $transactionTo];
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateRequestBody(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($request->all(), [
            'payer' => 'required|string|between:11,14|different:payee',
            'payee' => 'required|string|between:11,14',
            'amount' => 'required|integer|min:1'
        ]);
    }


    public function store(Request $request)
    {
        Log::info('Creating a new transaction');

        $validator = $this->validateRequestBody($request);

        if ($validator->fails())
            return $this->response('', $validator->errors()->toArray(), 400);

        try {
            $userFrom = $this->getUserByDocumentValue($request['payer']);
            $userTo = $this->getUserByDocumentValue($request['payee']);

            $rules = $this->userRules($userFrom, $userTo, $request['amount']);

            if ($rules)
                return $rules;

            list($transactionFrom, $transactionTo) = $this->convertTransaction($userFrom, $userTo, $request);

            $userFrom->wallet->amount -= $transactionFrom->amount;

            $this->saveAll($transactionFrom, $transactionTo, $userFrom);

            $this->publish('mars-authorize_transaction', $transactionFrom->toArray());
            $test = new AuthorizeTransactionConsumer();
            $test2 = new TransactionNotPaidConsumer();
            $test->process();
            $test2->process();
            Log::info('TransactionFrom ' . $transactionFrom->id . ' was created');

            return $this->response('Success', $transactionFrom->toArray(), 201);
        } catch (Throwable $e) {
            Log::error("Error trying create a new transaction. MESSAGE: " . $e->getMessage(), [$e->getTraceAsString()]);

            return $this->response('Internal server error' . $e->getMessage(), [], 500);
        }
    }
}
