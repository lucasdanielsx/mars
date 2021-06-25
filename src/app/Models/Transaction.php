<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Transaction extends Model
{
    use HasFactory;
    /**
     * @var Uuid
     */
    private mixed $id;
    /**
     * @var Uuid
     */
    private mixed $fk_wallet_from;
    /**
     * @var Uuid
     */
    private mixed $fk_wallet_to;
    /**
     * @var int
     */
    private mixed $amount;
    /**
     * @var string
     */
    private mixed $status;
    /**
     * @var array
     */
    private $payload;

    protected $casts = [
        'id' => 'string',
        'fk_wallet_from' => 'string',
        'fk_wallet_to' => 'string'
    ];
}
