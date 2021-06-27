<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransactionFrom extends BaseModel
{
    use HasFactory;

    protected $keyType = 'string';

    private $id;
    private $fkWalletId;
    private $amount;
    private $status;
    private $payload;
    private $createdAt;
    private $updatedAt;

    protected $table = 'transactions_from';

    protected $fillable = [
        "id",
        "fk_wallet_id",
        "amount",
        "status",
        "payload",
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
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
     * @return HasOne
     */
    public function transaction()
    {
        return $this->hasOne(TransactionTo::class, 'fk_transaction_from_id');
    }

    /**
     * @return HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'fk_transaction_from_id', 'id');
    }
}
