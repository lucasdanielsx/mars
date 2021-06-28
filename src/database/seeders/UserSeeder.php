<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            DB::beginTransaction();

            $customer = User::create([
                'id' => Uuid::uuid4(),
                'name' => 'Test Test',
                'email' => 'TestTest@gmail.com',
                'document_value' => '12345678910',
                'type' => 'customer'
            ]);

            $customer->wallet()->create([
                'id' => Uuid::uuid4(),
                'amount' => 100000
            ]);

            $store = User::create([
                'id' => Uuid::uuid4(),
                'name' => 'Test',
                'email' => 'Test@gmail.com',
                'document_value' => '12345678910100',
                'type' => 'store'
            ]);

            $store->wallet()->create([
                'id' => Uuid::uuid4(),
                'amount' => 100000
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
        }
    }
}
