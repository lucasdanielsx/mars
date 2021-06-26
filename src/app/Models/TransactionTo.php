<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\UuidInterface;

class TransactionTo extends Model
{
    use HasFactory;

    /** @var UuidInterface */
    private $id;

    /** @var UuidInterface */
    private $fkWalletId;

    /** @var UuidInterface */
    private $fkTransactionFromId;

    /** @var int */
    private $amount;

    /** @var string */
    private $status;

    /** @var array */
    private $payload;

    /** @var DateTime */
    private $createdAt;

    /** @var DateTime */
    private $updatedAt;

    protected $fillable = [
        "id",
        "fk_transaction_from_id",
        "fk_wallet_id",
        "amount",
        "status",
        "payload",
        "updated_at",
        "created_at"
    ];

    protected $casts = [
        'id' => 'string',
        'fk_wallet_from' => 'string',
        'fk_wallet_to' => 'string',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d'
    ];

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @param UuidInterface $id
     */
    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    /**
     * @return UuidInterface
     */
    public function getFkWalletId(): UuidInterface
    {
        return $this->fkWalletId;
    }

    /**
     * @param UuidInterface $fkWalletId
     */
    public function setFkWalletId(UuidInterface $fkWalletId): void
    {
        $this->fkWalletId = $fkWalletId;
    }

    /**
     * @return UuidInterface
     */
    public function getFkTransactionFromId(): UuidInterface
    {
        return $this->fkTransactionFromId;
    }

    /**
     * @param UuidInterface $fkTransactionFromId
     */
    public function setFkTransactionFromId(UuidInterface $fkTransactionFromId): void
    {
        $this->fkTransactionFromId = $fkTransactionFromId;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return BelongsTo
     */
    public function getWallet()
    {
        return $this->belongsTo(Wallet::class, 'fk_wallet_id');
    }

    /**
     * @return BelongsTo
     */
    public function getTransactionFrom()
    {
        return $this->belongsTo(Wallet::class, 'fk_transaction_from_id');
    }
}
