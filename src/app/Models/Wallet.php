<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Wallet extends Model
{
    use HasFactory;

    /**
     * @var Uuid
     */
    private $id;
    /**
     * @var Uuid
     */
    private $fk_user_id;
    /**
     * @var int
     */
    private $amount;

    /** @var DateTime */
    private $createdAt;

    /** @var DateTime */
    private $updatedAt;

    protected $fillable = [
        'id',
        'fk_user_id',
        'amount',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'fk_user_id' => 'string',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d'
    ];

    public function getUser()
    {
        return $this->belongsTo(User::class, 'fk_user_id');
    }

    public function getTransactionsFrom()
    {
        return $this->hasMany(TransactionFrom::class, 'fk_wallet_id', 'id');
    }

    public function getTransactionsTo()
    {
        return $this->hasMany(TransactionTo::class, 'fk_wallet_id', 'id');
    }
}
