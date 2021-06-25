<?php

namespace App\Http\Controllers;

use App\Jobs\NewTransactionJob;
use App\Jobs\ProcessPodcast;
use App\Models\Transaction;
use App\Models\User;
use Aws\Sqs\SqsClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Creating a new transaction');

        $validator = $this->validateRequestBody($request);

        if($validator->fails())
            return $this->response('', $validator->errors()->toArray(), 400);

        try{
            $userFrom = $this->getUser($request['payer']);
            if(empty($userFrom))
                return $this->response('Payer not exist', [], 400);

            if($userFrom->type != "CUSTOMER")
                return $this->response("Payer isn't allowed to make transactions", [], 403);

            if($userFrom->wallet->amount <= $request['amount'])
                return $this->response("Insufficient funds", [], 400);

            $userTo = $this->getUser($request['payee']);
            if(empty($userTo))
                return $this->response('Payee not exist', [], 400);

            $transaction = $this->mountTransaction($userFrom, $userTo, $request);

            $transaction->save();

            NewTransactionJob::dispatch($transaction)->onQueue('nonexistent_subscribe');

            Log::info('Transaction ' . $transaction->id . ' was created');

            return $this->response('Success', $transaction->toArray(), 201);
        } catch (\Throwable $e) {
            Log::error("Error trying create a new transaction. MESSAGE: " . $e->getMessage(), [$e->getTraceAsString()]);

            return $this->response('Internal server error', [], 500);
        }
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

    /**
     * @param $payer
     * @return mixed
     */
    private function getUser($payer)
    {
        return User::where('document_value', '=', $payer)->first();
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
        $transaction->status = 'new';
        $transaction->payload = $request->getContent();

        return $transaction;
    }

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
}
