<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends BaseModel
{
    use HasFactory;

    private $id;
    private $fkUserId;
    private $amount;
    private $createdAt;
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

    public function user()
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
