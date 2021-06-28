<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    public function test_no_body()
    {
        $response = $this->post('/api/v1/transactions');

        $response->assertStatus(400);
        $response->assertExactJson($this->getJsonFileResponse('transaction_controller/test_no_body.json'));
    }

    public function test_wrong_bodies()
    {
        /**
         * invalid payer less than 11 digits
         */
        $response = $this->post('/api/v1/transactions', $this->getJsonFileRequest('transaction_controller/test_wrong_bodies_1.json'));
        $response->assertStatus(400);
        $response->assertExactJson($this->getJsonFileResponse('transaction_controller/test_wrong_bodies_1.json'));

        /**
         * invalid payer more than 14 digits
         */
        $response = $this->post('/api/v1/transactions', $this->getJsonFileRequest('transaction_controller/test_wrong_bodies_2.json'));
        $response->assertStatus(400);
        $response->assertExactJson($this->getJsonFileResponse('transaction_controller/test_wrong_bodies_2.json'));

        /**
         * invalid payee less than 11 digits
         */
        $response = $this->post('/api/v1/transactions', $this->getJsonFileRequest('transaction_controller/test_wrong_bodies_3.json'));
        $response->assertStatus(400);
        $response->assertExactJson($this->getJsonFileResponse('transaction_controller/test_wrong_bodies_3.json'));

        /**
         * invalid amount (0)
         */
        $response = $this->post('/api/v1/transactions', $this->getJsonFileRequest('transaction_controller/test_wrong_bodies_4.json'));
        $response->assertStatus(400);
        $response->assertExactJson($this->getJsonFileResponse('transaction_controller/test_wrong_bodies_4.json'));

        /**
         * invalid amount (-1)
         */
        $response = $this->post('/api/v1/transactions', $this->getJsonFileRequest('transaction_controller/test_wrong_bodies_5.json'));
        $response->assertStatus(400);
        $response->assertExactJson($this->getJsonFileResponse('transaction_controller/test_wrong_bodies_5.json'));

        /**
         * invalid payee more than 14 digits
         */
        $response = $this->post('/api/v1/transactions', $this->getJsonFileRequest('transaction_controller/test_wrong_bodies_6.json'));
        $response->assertStatus(400);
        $response->assertExactJson($this->getJsonFileResponse('transaction_controller/test_wrong_bodies_6.json'));
    }
}
