<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ramsey\Uuid\Uuid;

class User extends Model
{
    /**
     * @var Uuid
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $password;
    /**
     * @var string
     */
    private $document_value;
    /**
     * @var DateTime
     */
    private $created_at;
    /**
     * @var DateTime
     */
    private $updated_at;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'document_value'
    ];

    /**
     * @return HasOne
     */
    public function getWallet()
    {
        return $this->hasOne(Wallet::class);
    }
}
