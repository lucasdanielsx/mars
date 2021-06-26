<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Event extends Model
{
    use HasFactory;

    /**
     * @var Uuid
     */
    private $id;
    /**
     * @var Uuid
     */
    private $fk_transaction_id;
    /**
     * @var string
     */
    private $type;
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
        "fk_transaction_id",
        "type",
        "payload",
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
        'fk_transaction_id' => 'string'
    ];
}
