<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        Log::info($request);

        if($request['payer'] == $request['payee']) return (new Response(['payee' => 'invalid'], 400))->header('Content-Type', 'application/json');
        if($request['amount'] <= 0) return (new Response(['amount' => 'invalid'], 400))->header('Content-Type', 'application/json');

        $userFrom = User::where('document_value', '=', $request['payer'])->first();
        if(empty($userFrom)) return (new Response(['payer' => 'invalid'], 400))->header('Content-Type', 'application/json');

        $userTo = User::where('document_value', '=', $request['payer'])->first();
        if(empty($userTo)) return (new Response(['payee' => 'invalid'], 400))->header('Content-Type', 'application/json');

        Log::info($userFrom->wallet()->id);

        $transaction = new Transaction();
        $transaction->id = Uuid::uuid4();
        $transaction->fk_wallet_from = $userFrom->wallet()->id;
        $transaction->fk_wallet_to = $userFrom->wallet()->id;
        $transaction->amount = $request['amount'];
        $transaction->status = 'new';
        $transaction->payload = $request;

        Transaction::create($transaction);
    }
}
