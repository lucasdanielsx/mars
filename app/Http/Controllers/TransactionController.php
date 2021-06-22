<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        Log::info($request);

        if($request['payer'] == $request['payee']) return (new Response(['payee' => 'invalid'], 400))->header('Content-Type', 'application/json');
        if($request['amount'] <= 0) return (new Response(['amount' => 'invalid'], 400))->header('Content-Type', 'application/json');

        $userFrom = User::where('name', '=', $request['payer'])->first();
        if(empty($userFrom)) return (new Response(['payer' => 'invalid'], 400))->header('Content-Type', 'application/json');

        $userTo = User::where('name', '=', $request['payer'])->first();
        if(empty($userTo)) return (new Response(['payee' => 'invalid'], 400))->header('Content-Type', 'application/json');

        Transaction::create(new Transaction([123, $userFrom['id'],$userTo['id'], $request['amount'], 'new']));
    }
}
