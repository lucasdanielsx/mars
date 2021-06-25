<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Wallet extends Model
{
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

    protected $fillable = [
        'id',
        'fk_wallet_from',
        'fk_wallet_to',
        'amount',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'fk_user_id' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'fk_user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
