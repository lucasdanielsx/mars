<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends BaseModel
{
    use HasFactory;

    private $id;
    private $fkTransactionFromId;
    private $type;
    private $payload;
    private $messageId;
    private $createdAt;
    private $updatedAt;

    protected $fillable = [
        "id",
        "fk_transaction_from_id",
        "type",
        "payload",
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
        'fk_transaction_id' => 'string',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d'
    ];

    public function transaction()
    {
        return $this->belongsTo(TransactionFrom::class, 'fk_transaction_from_id');
    }
}
