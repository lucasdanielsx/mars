<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends BaseModel
{
    use HasFactory;

    protected $keyType = 'string';

    private $id;
    private $name;
    private $email;
    private $type;
    private $documentValue;
    private $createdAt;
    private $updatedAt;

    protected $fillable = [
        'id',
        'name',
        'email',
        'document_value',
        'type',
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d'
    ];

    /**
     * @return HasOne
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'fk_user_id', 'id');
    }
}
