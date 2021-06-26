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
    private $id;
    /**
     * @var Uuid
     */
    private $fk_wallet_from;
    /**
     * @var Uuid
     */
    private $fk_wallet_to;
    /**
     * @var int
     */
    private $amount;
    /**
     * @var string
     */
    private $status;
    /**
     * @var array
     */
    private $payload;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "fk_wallet_from",
        "fk_wallet_to",
        "amount",
        "status",
        "payload",
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
        'fk_wallet_from' => 'string',
        'fk_wallet_to' => 'string'
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
