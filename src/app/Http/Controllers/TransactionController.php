<?php

namespace App\Http\Controllers;

use App\Consumers\AuthorizeTransactionConsumer;
use App\Helpers\SqsHelper;
use App\Models\Transaction;
use App\Models\User;
use Aws\Sqs\SqsClient;
use Illuminate\Database\Eloquent\Model;
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
        $sqsClient = new SqsClient([
            'profile' => 'default',
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => '2012-11-05'
        ]);

        $sqsHelper = new SqsHelper($sqsClient);
        $sqsHelper->sendMessage($queue, $message);
    }

    /**
     * @param Transaction $transaction
     * @param $userFrom
     */
    private function saveAll(Transaction $transaction, $userFrom): void
    {
        DB::transaction(function () use ($transaction, $userFrom) {
            $transaction->save();
            $userFrom->wallet->amount = $userFrom->wallet->amount - $transaction->amount;
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
     * @return Transaction
     */
    private function mountTransaction(User $userFrom, User $userTo, Request $request): Transaction
    {
        $transaction = new Transaction();
        $transaction->id = Uuid::uuid4();
        $transaction->fk_wallet_from = $userFrom->wallet->id;
        $transaction->fk_wallet_to = $userTo->wallet->id;
        $transaction->amount = $request['amount'];
        $transaction->status = 'created';
        $transaction->payload = $request->getContent();

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

            $transaction = $this->mountTransaction($userFrom, $userTo, $request);

            $this->saveAll($transaction, $userFrom);

            $this->publish('mars-authorize_transaction', $transaction->toArray());
            $test = new AuthorizeTransactionConsumer();
            $test->process();
            Log::info('Transaction ' . $transaction->id . ' was created');

            return $this->response('Success', $transaction->toArray(), 201);
        } catch (\Throwable $e) {
            Log::error("Error trying create a new transaction. MESSAGE: " . $e->getMessage(), [$e->getTraceAsString()]);

            return $this->response('Internal server error', [], 500);
        }
    }
}
