<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validator = $this->validateRequestBody($request);

        if($validator->fails())
            return $this->response('', $validator->errors()->toArray(), 400);

        $user = User::where('email', $request['email'])->first();

        list($user, $wallet) = $this->mountUserAndWallet($request);

        try {
            $this->saveAll($user, $wallet);

            return $this->response('Internal server error', $user->toArray(), 201);
        } catch (\Throwable $e) {
            Log::error("Error trying save a new user. MESSAGE: " . $e->getMessage(), [$e->getTraceAsString()]);

            return $this->response('Internal server error', [], 500);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateRequestBody(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //TODO function to valid cpf or cnpj

        return Validator::make($request->all(), [
            'name' => 'required|max:255: ',
            'email' => 'required|email',
            'document_value' => 'required|integer|digits_between:11,14'
        ]);
    }

    /**
     * @param Request $request
     * @return array (User, Wallet)
     */
    private function mountUserAndWallet(Request $request): array
    {
        $userId = Uuid::uuid4();

        $user = new User();
        $user->id = $userId;
        $user->name = $request['name'];
        $user->email = $request['email'];
        $user->document_value = $request['document_value'];
        $user->type = "CUSTOMER"; //TODO enum CUSTOMER, STORE

        $wallet = new Wallet();
        $wallet->id = Uuid::uuid4();
        $wallet->fk_user_id = $userId;
        $wallet->amount = 0;

        return [$user, $wallet];
    }

    /**
     * @param User $user
     * @param Wallet $wallet
     * @return void
     */
    private function saveAll(User $user, Wallet $wallet): void
    {
        DB::transaction(function () use ($user, $wallet) {
            $user->save();
            $wallet->save();
        });
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
