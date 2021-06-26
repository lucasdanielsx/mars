<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsToTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions_to', function (Blueprint $table) {
            $table->uuid('id')->unique()->primary();
            $table->foreignUuid('fk_wallet_id')->constrained('wallets');
            $table->foreignUuid('fk_transaction_from_id')->constrained('transactions_from');;
            $table->integer('amount');
            $table->string('status');
            $table->jsonb('payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions_to');
    }
}
