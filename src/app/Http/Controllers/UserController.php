<?php

namespace App\Http\Controllers;

use App\Helpers\Enums\UserType;
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
        Log::info('Creating a new user: ' . $request['name']);

        $validator = $this->validateRequestBody($request);

        if($validator->fails())
            return $this->response('', $validator->errors()->toArray(), 400);

        list($newUser, $newWallet) = $this->convertUserAndWallet($request);

        try {
            $user = User::where('document_value', $request['document_value'])->orWhere('email', $request['email'])->first();

            //TODO valid when are 2 lines
            if(!empty($user))
                return $this->response('Resource already exists', [], 409);

            $this->saveAll($newUser, $newWallet);

            Log::info('User ' . $request['name'] . ' was created');

            return $this->response('Success', $newUser->toArray(), 201);
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
            'name' => 'required|max:255',
            'email' => 'required|email',
            'document_value' => 'required|string|between:11,14'
        ]);
    }

    /**
     * @param Request $request
     * @return array (User, Wallet)
     */
    private function convertUserAndWallet(Request $request): array
    {
        $userId = Uuid::uuid4();

        $user = new User();
        $user->id =$userId;
        $user->name = $request['name'];
        $user->email = $request['email'];
        $user->documentValue = $request['document_value'];
        $user->type = UserType::CUSTOMER;

        $wallet = new Wallet();
        $wallet->id = Uuid::uuid4();
        $wallet->fkUserId = $userId;
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
