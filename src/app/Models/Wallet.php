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
        'fk_user_id',
        'amount',
        'created_at',
        'updated_at'
    ];

    public function getUser()
    {
        return $this->belongsTo(User::class);
    }
}
