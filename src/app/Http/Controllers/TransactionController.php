<?php

namespace App\Http\Controllers;

use App\Consumers\TransactionNotPaidConsumer;
use App\Helpers\Sqs\SqsHelper;
use App\Helpers\Sqs\SqsUsEast1Client;
use App\Models\TransactionFrom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Boolean;
use Ramsey\Uuid\Uuid;

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
     * @param TransactionFrom $transaction
     * @param $userFrom
     */
    private function saveAll(TransactionFrom $transaction, $userFrom): void
    {
        DB::transaction(function () use ($transaction, $userFrom) {
            $transaction->save();
            $userFrom->wallet->amount = $userFrom->wallet->amount - $transaction->getAmount();
            $userFrom->wallet->update();
        });
    }

    /**
     * @param String $value
     * @return mixed
     */
    private function getUserByDocumentValue(String $value)
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

        if ($userFrom->type != "CUSTOMER")
            return $this->response("Payer isn't allowed to make transactions", [], 403);

        if ($userFrom->wallet->amount <= $amount)
            return $this->response("Insufficient funds", [], 400);

        if (empty($userTo))
            return $this->response('Payee not exist', [], 400);

        return false;
    }

    /**
     * @param User $userFrom
     * @param User $userTo
     * @param Request $request
     * @return TransactionFrom
     */
    private function convertTransaction(User $userFrom, User $userTo, Request $request): TransactionFrom
    {
        $transaction = new TransactionFrom();
        $transaction->setId(Uuid::uuid4());
        $transaction->setFkWalletFrom($userFrom->wallet->id);
        $transaction->setFkWalletTo($userTo->wallet->id);
        $transaction->setAmount($request['amount']);
        $transaction->setStatus('created');
        $transaction->setPayload(json_decode($request->getContent(), true));

        return $transaction;
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

            if($rules instanceof Boolean)
                return $rules;

            $transaction = $this->convertTransaction($userFrom, $userTo, $request);

            $this->saveAll($transaction, $userFrom);

            $this->publish('mars-authorize_transaction', $transaction->toArray());
            $test = new TransactionNotPaidConsumer();
            $test->process();
            Log::info('TransactionFrom ' . $transaction->id . ' was created');

            return $this->response('Success', $transaction->toArray(), 201);
        } catch (\Throwable $e) {
            Log::error("Error trying create a new transaction. MESSAGE: " . $e->getMessage(), [$e->getTraceAsString()]);

            return $this->response('Internal server error', [], 500);
        }
    }
}
