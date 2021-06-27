<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionTo extends BaseModel
{
    use HasFactory;

    protected $keyType = 'string';

    private $id;
    private $fkWalletId;
    private $fkTransactionFromId;
    private $amount;
    private $status;
    private $payload;
    private $createdAt;
    private $updatedAt;

    protected $table = 'transactions_to';

    protected $fillable = [
        "id",
        "fk_transaction_from_id",
        "fk_wallet_id",
        "amount",
        "status",
        "payload",
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
        'fk_transaction_from_id' => 'string',
        'fk_wallet_id' => 'string',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d'
    ];

    /**
     * @return BelongsTo
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'fk_wallet_id');
    }

    /**
     * @return BelongsTo
     */
    public function transactionFrom()
    {
        return $this->belongsTo(TransactionFrom::class, 'fk_transaction_from_id', 'id');
    }
}
